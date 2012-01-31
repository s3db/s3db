/*
 * TouchGraph LLC. Apache-Style Software License
 *
 *
 * Copyright (c) 2002 Alexander Shapiro. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer. 
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *
 * 3. The end-user documentation included with the redistribution,
 *    if any, must include the following acknowledgment:  
 *       "This product includes software developed by 
 *        TouchGraph LLC (http://www.touchgraph.com/)."
 *    Alternately, this acknowledgment may appear in the software itself,
 *    if and wherever such third-party acknowledgments normally appear.
 *
 * 4. The names "TouchGraph" or "TouchGraph LLC" must not be used to endorse 
 *    or promote products derived from this software without prior written 
 *    permission.  For written permission, please contact 
 *    alex@touchgraph.com
 *
 * 5. Products derived from this software may not be called "TouchGraph",
 *    nor may "TouchGraph" appear in their name, without prior written
 *    permission of alex@touchgraph.com.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESSED OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED.  IN NO EVENT SHALL TOUCHGRAPH OR ITS CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR 
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, 
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, 
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * ====================================================================
 *
 */

package com.touchgraph.graphlayout;

import  com.touchgraph.graphlayout.graphelements.*;

import  java.util.Vector;
import  java.util.Hashtable;
import  java.util.Enumeration;
 
/** LocalityUtils:  Utilities for switching locality.  Animation effects
  * require a reference to TGPanel.
  *     
  * @author   Alexander Shapiro                                        
  * @version  1.20
  */
public class LocalityUtils {

    TGPanel tgPanel;
    Locality locality;
    ShiftLocaleThread shiftLocaleThread;
    boolean fastFinishShift=false;  // If finish fast is true, quickly wrap up animation
    
    public LocalityUtils(Locality loc, TGPanel tgp) {
        locality = loc;
        tgPanel = tgp;
    }

    /** Mark for deletion nodes not contained within distHash. */
    private synchronized boolean markDistantNodes(final Hashtable distHash) {
        final boolean[] someNodeWasMarked = new boolean[1]; 
        someNodeWasMarked[0] = false;
        Boolean x;
        TGForEachNode fen = new TGForEachNode() {
            public void forEachNode(Node n) {
                if(!distHash.containsKey(n)) { 
                    n.markedForRemoval=true;
                    someNodeWasMarked[0] = true;
                }
            }
        };
        
        locality.forAllNodes(fen);
        return someNodeWasMarked[0];
    }

    private synchronized void removeMarkedNodes() {
        final Vector nodesToRemove = new Vector();
        
        TGForEachNode fen = new TGForEachNode() {
            public void forEachNode(Node n) {
                if(n.markedForRemoval)  {
                    nodesToRemove.addElement(n);
                    n.markedForRemoval=false;    
                }
            }    
        };
        synchronized(locality) {    
            locality.forAllNodes(fen);
            locality.removeNodes(nodesToRemove);
        }    
    }

    /** Add to locale nodes within radius distance of a focal node. */
    private synchronized void addNearNodes(Hashtable distHash, int radius) throws TGException {
        for ( int r=0; r<radius+1; r++ ) {
            Enumeration localNodes = distHash.keys();
            while (localNodes.hasMoreElements()) {
                Node n = (Node)localNodes.nextElement();
                if(!locality.contains(n) && ((Integer)distHash.get(n)).intValue()<=r) {
                    n.justMadeLocal = true;
                    locality.addNodeWithEdges(n);
                    if (!fastFinishShift) {
                        try { Thread.currentThread().sleep(50); } 
                        catch (InterruptedException ex) {}
                    }
                }
            }
        }
    }

    private synchronized void unmarkNewAdditions() {
        TGForEachNode fen = new TGForEachNode() {
            public void forEachNode(Node n) {
                n.justMadeLocal=false;
            }    
        };
        locality.forAllNodes(fen);
    }

    /** The thread that gets instantiated for doing the locality shift animation. */
    class ShiftLocaleThread extends Thread {
        Hashtable distHash;
        int radius;
        Node focusNode;
        ShiftLocaleThread(Node n, int r) {                                         
            radius = r;             
            focusNode = n;                        
            start();
         
        }
            
        public void run() {        
            synchronized (LocalityUtils.this) {
            //Make sure node hasn't been deleted
                if (!locality.getCompleteEltSet().contains(focusNode)) return;                        
                tgPanel.stopDamper();
                distHash = locality.getCompleteEltSet().calculateDistances(focusNode,radius);                
         
                try {
                    if (markDistantNodes(distHash)) {
                         for (int i=0;i<5&&!fastFinishShift;i++) {
                             Thread.currentThread().sleep(100);                      
                         }
                    }
                    removeMarkedNodes();
                    for (int i=0;i<1&&!fastFinishShift;i++) {
                        Thread.currentThread().sleep(100); 
                    }
                    addNearNodes(distHash,radius);
                    for (int i=0;i<4&&!fastFinishShift;i++) {
                        Thread.currentThread().sleep(100); 
                    }
                    unmarkNewAdditions();
                } catch ( TGException tge ) {
                    System.err.println("TGException: " + tge.getMessage());
                } catch (InterruptedException ex) {}    
                tgPanel.resetDamper();            
            }
        }
    }
    
    public void setLocale(Node n, final int radius) {                       
        if(shiftLocaleThread!=null && shiftLocaleThread.isAlive()) {
            fastFinishShift=true; //This should cause last locale shift to finish quickly
            while(shiftLocaleThread.isAlive())
                try { Thread.currentThread().sleep(100); } 
                catch (InterruptedException ex) {}
        }
        fastFinishShift=false;        
        shiftLocaleThread=new ShiftLocaleThread(n, radius);
    }

   /** Add to locale nodes that are one edge away from a given node.
     * This method does not utilize "fastFinishShift" so it's likely that 
     * synchronization errors will occur.
     */
    public void expandNode(final Node n) {
        new Thread() {
            public void run() {
                synchronized (LocalityUtils.this) {
                    if (!locality.getCompleteEltSet().contains(n)) return;                        
                    tgPanel.stopDamper();
                    for(int i=0;i<n.edgeNum();i++) {
                        Node newNode = n.edgeAt(i).getOtherEndpt(n);
                        if (!locality.contains(newNode)) {
                            newNode.justMadeLocal = true;
                            try {
                                locality.addNodeWithEdges(newNode);
                                Thread.currentThread().sleep(50); 
                            } catch ( TGException tge ) {
                                System.err.println("TGException: " + tge.getMessage());
                            } catch ( InterruptedException ex ) {}         
                        }
                        else if (!locality.contains(n.edgeAt(i))) {
                            locality.addEdge(n.edgeAt(i));
                        }
                    }
                    try { Thread.currentThread().sleep(200); } 
                    catch (InterruptedException ex) {}         
                    unmarkNewAdditions();
                    tgPanel.resetDamper();
                }
            }
        }.start();
    }
    
   /** Hides a node, and all the nodes attached to it.  Requires a focusNode as 
     * an input in order to figure out which nodes not to hide.  For instance, 
     * suppose that one chose to hide the central node in a tree.  Which branch 
     * do you keep?  The branch that contains the focus Node.
     */
    public synchronized void hideNode( final Node hideNode, final Node focusNode ) {
        if (hideNode==null || focusNode==null) return;
        new Thread() {
            public void run() {
                synchronized(LocalityUtils.this) {
                    if (!locality.getCompleteEltSet().contains(hideNode) ||
                        !locality.getCompleteEltSet().contains(focusNode)) return;                        
                    locality.removeNode(hideNode); //Necessary so that node is ignored in distances calculation.
                    Hashtable distHash = locality.calculateDistances(focusNode,20);
                    markDistantNodes(distHash);  
                    try {
                        locality.addNodeWithEdges(hideNode); //Once distances are calculated, we can add the node back
                        if (hideNode!=focusNode) hideNode.markedForRemoval = true;
                    } catch ( TGException tge ) {
                        System.err.println("TGException in LocalityUtils.hideNode(): " + tge.getMessage());
                    }
                
                    tgPanel.repaint();
                    try { Thread.currentThread().sleep(200); } 
                    catch (InterruptedException ex) {}         
                    removeMarkedNodes();
                    tgPanel.resetDamper();
                }
            }
        }.start();
    }

} // end com.touchgraph.graphlayout.LocalityUtils

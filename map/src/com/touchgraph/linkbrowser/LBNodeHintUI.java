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

/**  LBNodeHintUI:  A UserInterface that shows a hint when the mouse is 
  *  paused over a node.  Rather then adding a listener to each node,
  *  This UI tests the position of the mouse every time it is paused, and
  *  associates the hint with the node over which the mouse is paused.
  *
  *  If after the hint is displayed the mouse is moved and paused over an 
  *  empty space, or leaves the TGLinkBrowser frame, the hint disappears.
  *  If the mouse is paused over a new node, then the current hint is replaced
  *  by the hint for the new node.
  *  Moving the mouse over the hint allows it to remain active indefinately,
  *  which means that it can be scrolled, and eventually edited.
  *   
  *  @author   Alexander Shapiro                                        
  *  @version  1.20
  */

package com.touchgraph.linkbrowser;
import com.touchgraph.graphlayout.interaction.*;
import com.touchgraph.graphlayout.*;
 
import java.io.*;
import java.util.*;
import java.awt.*;
import java.awt.event.*;
import javax.swing.*;
import javax.swing.event.*;
import javax.swing.text.html.*;
import javax.swing.text.*;
import java.net.URL;

public class LBNodeHintUI extends TGAbstractMousePausedUI { 
    
    TGLinkBrowser tgLinkBrowser;
    
    boolean mousePressed;
    
    MouseAdapter hintML;
    LBNode hintNode;    
    JTextPane tpHint;
    JInternalFrame intfHint;        
    JScrollPane spHint;    
    int hintWidth=0;
    int hintHeight=0;
            
    public LBNodeHintUI(final TGLinkBrowser tglb) {
        super(tglb.getTGPanel());
        tgLinkBrowser = tglb;
        mousePressed= false;
        hintNode = null;
        hintML = new MouseAdapter() {
            public void mousePressed(MouseEvent e) { 
                mousePressed = true; 
                hideHint(); 
            }
            public void mouseReleased(MouseEvent e) { 
                mousePressed = false;   
            }
        };
                               
        tpHint = new JTextPane();
        tpHint.setEditable(false);
        tpHint.addHyperlinkListener(new HyperlinkListener() {
            public void hyperlinkUpdate(HyperlinkEvent e) {   
                if (e.getEventType() == HyperlinkEvent.EventType.ACTIVATED) 
                    tgLinkBrowser.processHintURL(e.getURL());                
            } 
        });

        spHint = new JScrollPane(tpHint);
        spHint.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED); 
        
        intfHint = new JInternalFrame();
        intfHint.setResizable(true);
        intfHint.setClosable(true);            
        intfHint.setDefaultCloseOperation(WindowConstants.HIDE_ON_CLOSE);
        intfHint.getContentPane().setLayout(new BorderLayout());
        intfHint.getContentPane().add(spHint);
        
        MouseAdapter cancelHintOnEntry = new MouseAdapter() { 
            public void mouseEntered(MouseEvent e) {
                //Canceling the mouse paused event means that hint does
                //not dissapear when the mouse is over it.     
                LBNodeHintUI.this.cancelPause();          
            } 
        };
        
        tpHint.addMouseListener(cancelHintOnEntry);  
        intfHint.addMouseListener(cancelHintOnEntry);     
    }
  
    public void showHint(LBNode n) {        
                //System.out.println("Enter node show hint");           
        if(intfHint.isVisible() || n.getHint().trim().equals("")) return;        
        intfHint.setTitle(n.getLabel());
        hintNode=n;
        hintWidth = n.getHintWidth();
        if (n.getHintIsHTML()) {
            tpHint.setEditorKit(new HTMLEditorKit());
            URL documentBase = tgLinkBrowser.getDocumentBase();            
            if (documentBase!=null) ((HTMLDocument) tpHint.getDocument()).setBase(documentBase);
        }
        else {
            tpHint.setEditorKit(new StyledEditorKit());
        }
        tpHint.setText(n.getHint());
                
	//System.out.println("Node hint set");        
        int ix = (int) n.drawx;
        int iy = (int) n.drawy;             
       
        spHint.getVerticalScrollBar().setVisible(false);
        hintHeight = n.getHintHeight();
        if(hintHeight<LBNode.MINIMUM_HINT_HEIGHT) {
            //Set the height to 10, because we don't care what it is.  We only need to set the
            //width so that hintHight can be correctly determined.
            tpHint.setSize(new Dimension(hintWidth-16,10));
            hintHeight = tpHint.getPreferredScrollableViewportSize().height+40;             
        }
        int topEdge = 0;
        int tgPanelHeight = tgPanel.getSize().height;
        int nodeHeight = n.getHeight()/2;
        if(iy>tgPanelHeight/2 || iy-nodeHeight>hintHeight) {
            hintHeight=Math.min(iy-nodeHeight,hintHeight);                
            topEdge = iy-hintHeight-nodeHeight;
        }
        else {
            hintHeight=Math.min(tgPanelHeight-iy-nodeHeight,hintHeight);                
            topEdge = iy + nodeHeight;
        }
        int leftEdge=ix-hintWidth/2;
        leftEdge = Math.max(Math.min(leftEdge,tgPanel.getWidth()-hintWidth),0);
        
        intfHint.setSize(new Dimension(hintWidth,hintHeight));
        intfHint.setLocation(leftEdge,topEdge);  
        intfHint.setVisible(true);                              
        
    }
    
    public void hideHint() {
        intfHint.setVisible(false); 
    }
                
    public void preActivate() { 
       hideHint(); 
       tgPanel.addMouseListener(hintML);
       tgPanel.add(intfHint);
    }

    public void postDeactivate() { 
        tgPanel.removeMouseListener(hintML);
        tgPanel.remove(intfHint);
    }
    
    public void mousePaused(MouseEvent e) {      
        //System.out.println("mouse paused over node");  
        LBNode mouseOverN = (LBNode) tgPanel.getMouseOverN();
        if(mouseOverN!=null && !mousePressed) {
            if(hintNode!=mouseOverN) hideHint();
            showHint(mouseOverN); 
        }
        else 
            hideHint();
        tgPanel.repaint();
    }
    
    public void mouseMoved(MouseEvent e) {
        mousePressed = false;  //If the mouse was pressed, mouseDragged would have been called
    }
    
    public void mouseDragged(MouseEvent e) {
        if(intfHint.isVisible()) {
            hideHint();
            tgPanel.repaint();
        }
    }
}

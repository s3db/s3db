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

/**  LBEdgeLabelUI:  A UserInterface that shows a label when the mouse is 
  *  paused over an edge.  Rather then adding a listener to each edge,
  *  This UI tests the position of the mouse every time it is paused, and
  *  associates the label with the edge over which the mouse is paused.
  *
  *  If after the label is displayed the mouse is moved and paused over an 
  *  empty space, or leaves the TGLinkBrowser frame, the label disappears.
  *  If the mouse is paused over a new edge, then the current label is replaced
  *  by the label for the new edge.
  *  Moving the mouse over the label allows it to remain active indefinately,
  *  which means that it can be scrolled, and eventually edited.
  *  
  *  This class is modified by Chuming Chen based on LBNodeLabelUI.java    
  *  @author   Alexander Shapiro                                        
  *  @author   Chuming Chen
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

public class LBEdgeLabelUI extends TGAbstractMousePausedUI { 
    
    TGLinkBrowser tgLinkBrowser;
    
    boolean mousePressed;
    
    MouseAdapter labelML;
    LBEdge labelEdge;    
    JTextPane tpLabel;
    JInternalFrame intfLabel;        
    JScrollPane spLabel;    
    int labelWidth=0;
    int labelHeight=0;
            
    public LBEdgeLabelUI(final TGLinkBrowser tglb) {
        super(tglb.getTGPanel());
        tgLinkBrowser = tglb;
        mousePressed= false;
        labelEdge = null;
        labelML = new MouseAdapter() {
            public void mousePressed(MouseEvent e) { 
                mousePressed = true; 
                hideLabel(); 
            }
            public void mouseReleased(MouseEvent e) { 
                mousePressed = false;   
            }
        };
                               
        tpLabel = new JTextPane();
        tpLabel.setEditable(false);
        tpLabel.addHyperlinkListener(new HyperlinkListener() {
            public void hyperlinkUpdate(HyperlinkEvent e) {   
                if (e.getEventType() == HyperlinkEvent.EventType.ACTIVATED) 
                    tgLinkBrowser.processHintURL(e.getURL());                
            } 
        });

        spLabel = new JScrollPane(tpLabel);
        spLabel.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED); 
        
        intfLabel = new JInternalFrame();
        intfLabel.setResizable(true);
        intfLabel.setClosable(true);            
        intfLabel.setDefaultCloseOperation(WindowConstants.HIDE_ON_CLOSE);
        intfLabel.getContentPane().setLayout(new BorderLayout());
        intfLabel.getContentPane().add(spLabel);
        
        MouseAdapter cancelLabelOnEntry = new MouseAdapter() { 
            public void mouseEntered(MouseEvent e) {
                //Canceling the mouse paused event means that label does
                //not dissapear when the mouse is over it.                
                LBEdgeLabelUI.this.cancelPause();          
            } 
        };
        
        tpLabel.addMouseListener(cancelLabelOnEntry);  
        intfLabel.addMouseListener(cancelLabelOnEntry);     
    }
  
    public void showLabel(LBEdge edge, MouseEvent e) {      
        //edge.setLabel("Jasdfasdfasdfasdfasfdasdfasfdasdfasdfasdfasdfasdfasdfasdust a hint hint hint hitn htintihthkkkkasfsdasdf");  
        if(intfLabel.isVisible() || edge.getLabel().trim().equals("")) return;        
        intfLabel.setTitle("" + edge.getFrom().getLabel().trim() + " and " + edge.getTo().getLabel().trim());
        labelEdge=edge;
        labelWidth = edge.getLabelWidth();
        if (edge.getLabelIsHTML()) {
            tpLabel.setEditorKit(new HTMLEditorKit());
            URL documentBase = tgLinkBrowser.getDocumentBase();            
            if (documentBase!=null) ((HTMLDocument) tpLabel.getDocument()).setBase(documentBase);
        }
        else {
            tpLabel.setEditorKit(new StyledEditorKit());
        }
        tpLabel.setText(edge.getLabel());
                        
        //int ix = (int) edge.drawx;
        //int iy = (int) edge.drawy;             
        int ix = e.getPoint().x;
        int iy = e.getPoint().y;
 
        spLabel.getVerticalScrollBar().setVisible(false);
        labelHeight = edge.getLabelHeight();
        if(labelHeight<LBEdge.MINIMUM_LABEL_HEIGHT) {
            //Set the height to 10, because we don't care what it is.  We only need to set the
            //width so that labelHight can be correctly determined.
            tpLabel.setSize(new Dimension(labelWidth-16,10));
            labelHeight = tpLabel.getPreferredScrollableViewportSize().height+40;             
        }
        int topEdge = 0;
        int tgPanelHeight = tgPanel.getSize().height;
        //int edgeHeight = edge.getHeight()/2;
        int edgeHeight = 0;
        if(iy>tgPanelHeight/2 || iy-edgeHeight>labelHeight) {
            labelHeight=Math.min(iy-edgeHeight,labelHeight);                
            topEdge = iy-labelHeight-edgeHeight;
        }
        else {
            labelHeight=Math.min(tgPanelHeight-iy-edgeHeight,labelHeight);                
            topEdge = iy + edgeHeight;
        }
        
        //topEdge = iy - 10; 
        int leftEdge=ix-labelWidth/2;
        leftEdge = Math.max(Math.min(leftEdge,tgPanel.getWidth()-labelWidth),0);
        
        intfLabel.setSize(new Dimension(labelWidth,labelHeight));
        intfLabel.setLocation(leftEdge,topEdge);  
        intfLabel.setVisible(true);                              
    /*    System.out.println("ix: " + ix); 
        System.out.println("iy: " + iy); 
        System.out.println("topEdge: " + topEdge); 
        System.out.println("leftEdge: " + leftEdge); 
     */
    }
    
    public void hideLabel() {
        intfLabel.setVisible(false); 
    }
                
    public void preActivate() { 
       hideLabel(); 
       tgPanel.addMouseListener(labelML);
       tgPanel.add(intfLabel);
    }

    public void postDeactivate() { 
        tgPanel.removeMouseListener(labelML);
        tgPanel.remove(intfLabel);
    }
    
    public void mousePaused(MouseEvent e) {     
        //System.out.println("mouse paused over edge");   
        LBEdge mouseOverE = (LBEdge) tgPanel.getMouseOverE();
        if(mouseOverE!=null && !mousePressed) {
            if(labelEdge!=mouseOverE) hideLabel();
            showLabel(mouseOverE, e); 
        }
        else 
            hideLabel();
        tgPanel.repaint();
    }
    
    public void mouseMoved(MouseEvent e) {
        mousePressed = false;  //If the mouse was pressed, mouseDragged would have been called
    }
    
    public void mouseDragged(MouseEvent e) {
        if(intfLabel.isVisible()) {
            hideLabel();
            tgPanel.repaint();
        }
    }
}

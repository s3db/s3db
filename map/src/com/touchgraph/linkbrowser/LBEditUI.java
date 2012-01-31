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

package com.touchgraph.linkbrowser;
import com.touchgraph.graphlayout.interaction.*;
import com.touchgraph.graphlayout.*;

import  javax.swing.*;
import  javax.swing.event.*;
import  java.awt.Font;
import  java.awt.event.*;
import  java.applet.*;
import  java.io.*;
import  java.util.*;

/**  LBEditUI:  User Interface for editing the graph.
  *   
  *  Author:  Alexander Shapiro                                        
  *  Version: 1.20
  */

public class LBEditUI extends TGUserInterface {
    TGLinkBrowser tgLinkBrowser;
	TGPanel tgPanel;	
	
	LBDragAddNodeUI dragAddNodeUI;
	DragNodeUI dragNodeUI;
	TGAbstractClickUI switchSelectUI;
    TGAbstractDragUI hvDragUI;
    
    LBNode popupNode=null;
    LBEdge popupEdge=null;
    JPopupMenu nodePopup;
    JPopupMenu edgePopup;
//    JPopupMenu backPopup;
	
	LBEditMouseListener ml;
	LBEditMouseMotionListener mml;
	
    public LBEditUI(TGLinkBrowser tglb) {
        tgLinkBrowser = tglb;
        tgPanel = tgLinkBrowser.getTGPanel();       
		 
		ml = new LBEditMouseListener();
		mml = new LBEditMouseMotionListener();
		dragAddNodeUI = new LBDragAddNodeUI(tgLinkBrowser);	
		dragNodeUI = new DragNodeUI(tgPanel);	
		switchSelectUI = tgPanel.getSwitchSelectUI();
        hvDragUI = tgLinkBrowser.hvScroll.getHVDragUI();
        
        setUpNodePopup();
        setUpEdgePopup();
//        setUpBackPopup();       
	}
	
	public void activate() {		
		tgPanel.addMouseListener(ml);
		tgPanel.addMouseMotionListener(mml);	
	}
	
	public void deactivate() {
		tgPanel.removeMouseListener(ml);
		tgPanel.removeMouseMotionListener(mml);		
	}
	
	class LBEditMouseListener extends MouseAdapter {
		public void mousePressed(MouseEvent e) {
			LBNode mouseOverN = (LBNode) tgPanel.getMouseOverN();
			LBNode select = (LBNode) tgPanel.getSelect();			
			
			if (e.getModifiers() == MouseEvent.BUTTON1_MASK) { 
				if (mouseOverN != null) {
					if(mouseOverN!=select)
						dragNodeUI.activate(e);
					else
						dragAddNodeUI.activate(e);
				}
				else {
                    hvDragUI.activate(e);
				}
				
			}	
		}
		
		public void mouseClicked(MouseEvent e) {
			if (e.getModifiers() == MouseEvent.BUTTON1_MASK) 
				switchSelectUI.activate(e);
			
		}

        public void mouseReleased(MouseEvent e) {
            if (e.isPopupTrigger()) {
                popupNode = (LBNode) tgPanel.getMouseOverN();
                popupEdge = (LBEdge) tgPanel.getMouseOverE();
                if (popupNode!=null) {
                    tgPanel.setMaintainMouseOver(true);
                    tgPanel.removeMouseListener(ml); //So that dismissing popup does not add node
                    nodePopup.show(e.getComponent(), e.getX(), e.getY());
                }
                else if (popupEdge!=null) {
                    tgPanel.setMaintainMouseOver(true);
                    tgPanel.removeMouseListener(ml); //So that dismissing popup does not add node
                    edgePopup.show(e.getComponent(), e.getX(), e.getY());
                }
                else {
                    tgLinkBrowser.lbPopup.show(e.getComponent(), e.getX(), e.getY());
                }
                //else {
                //    backPopup.show(e.getComponent(), e.getX(), e.getY());
                //}
            }
         }       
	}

	class LBEditMouseMotionListener extends MouseMotionAdapter {
		public void mouseMoved(MouseEvent e) {			
			tgPanel.startDamper();
		}
	}		
	
    private void setUpNodePopup() {
        nodePopup = new JPopupMenu();
        JMenuItem menuItem;
        JMenu navigateMenu = new JMenu("Navigate");

        menuItem = new JMenuItem("Edit Node");
        ActionListener editNodeAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(popupNode!=null) {
                          tgPanel.setSelect(popupNode);
                          tgLinkBrowser.lbNodeDialog.setLBNode(popupNode);                                                                    
                          tgLinkBrowser.lbNodeDialog.showDialog();
                    }
                }
            };

        menuItem.addActionListener(editNodeAction);
        nodePopup.add(menuItem);
        
        menuItem = new JMenuItem("Expand Node");
        ActionListener expandAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(popupNode!=null) {
                        tgPanel.expandNode(popupNode);
                    }
                }
            };

        menuItem.addActionListener(expandAction);
        navigateMenu.add(menuItem);

        menuItem = new JMenuItem("Hide Node");
        ActionListener hideAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    Node select = tgPanel.getSelect();
                    if(popupNode!=null) {
                        tgPanel.hideNode(popupNode, select);
                    }
                }
            };

        menuItem.addActionListener(hideAction);
        navigateMenu.add(menuItem);

        nodePopup.add(navigateMenu);

        menuItem = new JMenuItem("Delete Node");
        ActionListener deleteNodeAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(popupNode!=null) {
                        tgPanel.deleteNode(popupNode);
                    }
                }
            };

        menuItem.addActionListener(deleteNodeAction);
        nodePopup.add(menuItem);

        nodePopup.addPopupMenuListener(new PopupMenuListener() {
            public void popupMenuCanceled(PopupMenuEvent e) {}
            public void popupMenuWillBecomeInvisible(PopupMenuEvent e) {
                tgPanel.setMaintainMouseOver(false);
                tgPanel.setMouseOverN(null);
                tgPanel.repaint();
                tgPanel.addMouseListener(ml);
            }
            public void popupMenuWillBecomeVisible(PopupMenuEvent e) {}
        });

    }

    private void setUpEdgePopup() {
        edgePopup = new JPopupMenu();
        JMenuItem menuItem;

        menuItem = new JMenuItem("Edit Edge");
        ActionListener editEdgeAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(popupEdge!=null) {                          
                          tgLinkBrowser.lbEdgeDialog.setLBEdge(popupEdge);                                                                    
                          tgLinkBrowser.lbEdgeDialog.showDialog();
                    }
                }
            };
        menuItem.addActionListener(editEdgeAction);
        edgePopup.add(menuItem);

        menuItem = new JMenuItem("Reverse Edge");
        ActionListener reverseEdgeAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(popupEdge!=null) {                                                
                        popupEdge.reverse();                        
                    }
                }
            };
        menuItem.addActionListener(reverseEdgeAction);
        edgePopup.add(menuItem);

        menuItem = new JMenuItem("Delete Edge");
        ActionListener deleteEdgeAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(popupEdge!=null) {
                        tgPanel.deleteEdge(popupEdge);
                    }
                }
            };
        menuItem.addActionListener(deleteEdgeAction);
        edgePopup.add(menuItem);

        edgePopup.addPopupMenuListener(new PopupMenuListener() {
            public void popupMenuCanceled(PopupMenuEvent e) {}
            public void popupMenuWillBecomeInvisible(PopupMenuEvent e) {
                tgPanel.setMaintainMouseOver(false);
                tgPanel.setMouseOverE(null);
                tgPanel.repaint();
                tgPanel.addMouseListener(ml);
            }
            public void popupMenuWillBecomeVisible(PopupMenuEvent e) {}
        });
    }
/*
    private void setUpBackPopup() {
        backPopup = new JPopupMenu();
        JMenuItem menuItem;

        menuItem = new JMenuItem("New Graph");
        ActionListener startOverAction = new ActionListener() {
                public void actionPerformed( ActionEvent e ) {
                    tgPanel.clearAll();
                    tgPanel.clearSelect();
                    try {
                        LBNode firstNode = new LBNode();
                        tgPanel.addNode(firstNode);
                        tgPanel.setSelect(firstNode);
                        tgLinkBrowser.lbNodeDialog.setLBNode(firstNode);                                                                    
                        tgLinkBrowser.lbNodeDialog.showDialog();
                    } catch ( TGException tge ) {
                        System.err.println(tge.getMessage());
                        tge.printStackTrace(System.err);
                    }
                    tgPanel.fireResetEvent();
                    tgPanel.repaint();
                }
            };
        menuItem.addActionListener(startOverAction);
        backPopup.add(menuItem);
    }
*/
}
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
import com.touchgraph.graphlayout.TGPanel;

import javax.swing.*;
import java.awt.*;
import java.awt.event.*;
import java.io.*;

/**  LBEdgeDialog:  A dialog for changing edge properties
  *
  *  Author:  Alexander Shapiro                                        
  *  Version: 1.20
  */

class LBEdgeDialog extends JInternalFrame {
    LBEdge lbEdge;
    TGPanel tgPanel;
    
    private JTextField tfEdgeColor;
    private JLabel lblEdgeColor;
    private JButton lastEdgeColorButton;
 
    private JComboBox cboEdgeType;
    private JTextField tfEdgeLength;
    
    private JButton cmdCancel;
    private JButton cmdOK;
    
    public LBEdgeDialog(TGPanel tgp) {
        super("Edge Properties");
        tgPanel = tgp;
        setClosable(true);            
        setDefaultCloseOperation(WindowConstants.HIDE_ON_CLOSE);
        getContentPane().setLayout(null);        
        
        setSize(new java.awt.Dimension(330, 170));
        setPreferredSize(new java.awt.Dimension(330, 170));
        
        initComponents();   
    }
    
    public void showDialog() {
        setVisible(true);
        setLocation((tgPanel.getWidth()-this.getWidth())/2,10);
        lastEdgeColorButton = null;
    }
        
    public void setLBEdge(LBEdge lbe) {
        lbEdge = lbe;
        tfEdgeLength.setText(""+lbEdge.getLength());
        setEdgeColor(lbEdge.getColor());      
        cboEdgeType.setSelectedIndex(lbEdge.getType());
    }    
    

    public void saveChangesAndHideDialog() {
        if(lbEdge==null) {
            this.setVisible(false);       
            return;
        }
        
        lbEdge.setColor(lblEdgeColor.getBackground());
        LBEdge.setEdgeDefaultColor(lblEdgeColor.getBackground());
        
        lbEdge.setType(cboEdgeType.getSelectedIndex());
        LBEdge.setEdgeDafaultType(cboEdgeType.getSelectedIndex());
        
        try {
            lbEdge.setLength(Integer.parseInt(tfEdgeLength.getText()));
            LBEdge.setEdgeDefaultLength(Integer.parseInt(tfEdgeLength.getText()));
        }
        catch(NumberFormatException e) {} //Don't change the hint width 
 
        this.setVisible(false);    
        if(tgPanel!=null) {
            tgPanel.repaint();
            tgPanel.resetDamper();
        }
    }
    
    private void initComponents() {
        JLabel lbl; // just a dummy variable

        tfEdgeLength = new JTextField();        
        cmdCancel = new JButton();
        cmdOK = new JButton();
                
        initColorSelectors();        

        String[] edgeTypes = {"Undirected Line", "Directed Arrow"};        
        cboEdgeType = new JComboBox(edgeTypes);
        
        lbl = new JLabel("Type");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(5, 40, 60, 17);
                
        getContentPane().add(cboEdgeType);
        cboEdgeType.setBounds(75, 40, 120, 20);
        
        getContentPane().add(tfEdgeLength);
        tfEdgeLength.setBounds(75, 68, 35, 21);

        lbl = new JLabel("Length");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(5, 70, 60, 17);

        Action cancelAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {              
                LBEdgeDialog.this.setVisible(false);        
            }
        };

        cmdCancel.setText("Cancel");
        cmdCancel.addActionListener(cancelAction);
        getContentPane().add(cmdCancel);
        cmdCancel.setBounds(105, 110, 80, 20);

        Action okAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {              
                saveChangesAndHideDialog();
            }
        };
        
        cmdOK.setText("OK");
        cmdOK.addActionListener(okAction);
        getContentPane().add(cmdOK);
        cmdOK.setBounds(195, 110, 100, 20);

                
        //pack();
    }
    
    private void initColorSelectors() {
        JLabel lbl; // just a dummy variable

        tfEdgeColor = new JTextField();        
        lblEdgeColor = new JLabel();
        
        lbl = new JLabel("Color");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(-15, 10, 80, 17);

        getContentPane().add(lblEdgeColor);
        lblEdgeColor.setBounds(76, 11, 18, 18);        
        lblEdgeColor.setOpaque(true);
        setEdgeColor(Color.decode("#0000B0"));

        getContentPane().add(tfEdgeColor);
        tfEdgeColor.setBounds(100, 10, 60, 21);
        tfEdgeColor.setHorizontalAlignment(SwingConstants.CENTER);     
        tfEdgeColor.addKeyListener(new KeyAdapter() {
            public void keyReleased(KeyEvent e) {
                try {
                    Color col = Color.decode("#"+tfEdgeColor.getText());
                    lblEdgeColor.setBackground(col);
                    if (lastEdgeColorButton!=null) lastEdgeColorButton.setBackground(col);                    
                } 
                catch (NumberFormatException ex) {}
            }
        });
        
        Action edgeColorAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {
                lastEdgeColorButton = (JButton) e.getSource();
                Color c = (lastEdgeColorButton).getBackground();                
                setEdgeColor(c);        
            }
        };
        
        Color[] edgeColors = new Color[] { 
            Color.decode("#0000B0"),
            Color.decode("#000000"),
            Color.decode("#808080"),                        
            Color.decode("#00B000"),                        
            Color.decode("#B0B000"),         
            Color.decode("#D00000")                               
        };

        for (int i=0; i<edgeColors.length; i++) {
            JButton edgeColorButton = new JButton(edgeColorAction);
            edgeColorButton.setBackground(edgeColors[i]);
            getContentPane().add(edgeColorButton);
            edgeColorButton.setBounds(165+i*22, 10, 20, 20);
        }
    }
    
    private void setEdgeColor(Color c) {
        lblEdgeColor.setBackground(c);
        tfEdgeColor.setText(encodeColor(c));
    }
    
    private String encodeColor(Color c) {
        if (c == null) return null;        
        int rgb = c.getRGB()&0xffffff;
        String zeros = "000000";
        String data = Integer.toHexString(rgb);
        return (zeros.substring(data.length()) + data).toUpperCase();
    }
    
    public static void main(String[] args) {        
        JFrame frame;
        frame = new JFrame("TEST");
        LBEdgeDialog nd = new LBEdgeDialog(null);
        frame.addWindowListener(new WindowAdapter() {
            public void windowClosing(WindowEvent e) {System.exit(0);}
        });
        
        nd.setVisible(true);
        
        frame.getContentPane().setLayout(new FlowLayout());
        frame.getContentPane().add(nd);
        frame.setSize(500,500);  
        frame.setVisible(true);         
    }
}

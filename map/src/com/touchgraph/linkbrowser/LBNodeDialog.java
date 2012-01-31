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

/**  LBNodeDialog:  A dialog for editing LBNode properties.
  *
  *  @author   Alexander Shapiro                                        
  *  @version  1.20
  */

class LBNodeDialog extends JInternalFrame {
    LBNode lbNode;
    TGPanel tgPanel;
    
    private JTextField tfLabel;
    
    private JTextField tfBackColor;
    private JTextField tfTextColor;
    private JButton lastBackColorButton;
    private JLabel lblBackColor;
    private JLabel lblTextColor;
    private JButton lastTextColorButton;
    
    private JComboBox cboFontSize;
    private JComboBox cboNodeType;
    
    private JTextField tfURL;
    private JCheckBox cbXML;
    private JCheckBox cbLocal;
    private JButton cmdBrowse;
    
    private JTextField tfHintWidth;
    private JTextField tfHintHeight;
    private JCheckBox cbHintIsHTML;
    private JTextArea taHint;
    
    private JButton cmdCancel;
    private JButton cmdOK;
    
    public LBNodeDialog(TGPanel tgp) {
        super("Node Properties");
        tgPanel = tgp;
        setClosable(true);            
        setDefaultCloseOperation(WindowConstants.HIDE_ON_CLOSE);
        getContentPane().setLayout(null);        
        
        setSize(new java.awt.Dimension(330, 385));
        setPreferredSize(new java.awt.Dimension(330, 385));
        

        initComponents();   
    }

    public void showDialog() {
        setVisible(true);
        setLocation((tgPanel.getWidth()-this.getWidth())/2,10);
        lastBackColorButton = null;
        lastTextColorButton = null;
    }
            
    public void setLBNode(LBNode lbn) {
        lbNode = lbn;
        tfLabel.setText(lbNode.getLabel());
        tfLabel.setCaretPosition(0);
        tfURL.setText(lbNode.getURL());
        tfURL.setCaretPosition(0);
        cbXML.setSelected(lbNode.getURLIsXML());
        cbLocal.setSelected(lbNode.getURLIsLocal());
        setTextColor(lbNode.getTextColor());
        setBackColor(lbNode.getBackColor());
        cboFontSize.setSelectedItem(String.valueOf(lbNode.getFont().getSize()));
        cboNodeType.setSelectedIndex(lbNode.getType()-1);
        taHint.setText(lbNode.getHint());
        tfHintWidth.setText("" + lbNode.getHintWidth());
        tfHintHeight.setText("" + lbNode.getHintHeight());
        cbHintIsHTML.setSelected(lbNode.getHintIsHTML());
    }    
    

    public void saveChangesAndHideDialog() {
        if(lbNode==null) {
            this.setVisible(false);       
            return;
        }
        
        lbNode.setLabel(tfLabel.getText());
        lbNode.setURL(tfURL.getText());
        lbNode.setURLIsXML(cbXML.isSelected());
        lbNode.setURLIsLocal(cbLocal.isSelected());
        
        lbNode.setTextColor(lblTextColor.getBackground());
        LBNode.setNodeTextColor(lblTextColor.getBackground());
        
        lbNode.setBackColor(lblBackColor.getBackground());
        LBNode.setNodeBackDefaultColor(lblBackColor.getBackground());
         
        lbNode.setHint(taHint.getText());
        lbNode.setHintIsHTML(cbHintIsHTML.isSelected());
        
        lbNode.setType(cboNodeType.getSelectedIndex()+1);
        LBNode.setNodeType(cboNodeType.getSelectedIndex()+1);
 
        try {
            lbNode.setHintWidth(Integer.parseInt(tfHintWidth.getText()));
        }
        catch(NumberFormatException e) {} //Don't change the hint width 

        try {
            lbNode.setHintHeight(Integer.parseInt(tfHintHeight.getText()));
        }
        catch(NumberFormatException e) {} //Don't change the hint width 
        
        try {
            lbNode.setFont(new Font(LBNode.TEXT_FONT.getFamily(), Font.PLAIN,
                Integer.parseInt((String) cboFontSize.getSelectedItem())));
        }
        catch(NumberFormatException e) {} //Don't change the font 
 
        this.setVisible(false);    
        if(tgPanel!=null) tgPanel.repaint();
    }
    
    private void initComponents() {
        JLabel lbl; // just a dummy variable

        tfLabel = new JTextField();
        
        tfURL = new JTextField();
        cbXML = new JCheckBox();
        cbLocal = new JCheckBox();
        //cmdBrowse = new JButton();
    
        cbHintIsHTML = new JCheckBox();
        tfHintWidth = new JTextField();
        tfHintHeight = new JTextField();
        taHint = new JTextArea();
        
        cmdCancel = new JButton();
        cmdOK = new JButton();
                        
        lbl = new JLabel("Label");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(10, 20, 60, 17);

        getContentPane().add(tfLabel);
        tfLabel.setBounds(80, 19, 220, 21);
                
        initColorSelectors();        

        String[] fontSizes = { "6", "8", "9", "10", "11", "12", "14", "16", "18", "20", "24"};        
        cboFontSize = new JComboBox(fontSizes);

        lbl = new JLabel("Font Size");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(10, 100, 60, 17);
                
        getContentPane().add(cboFontSize);
        cboFontSize.setBounds(80, 100, 50, 20);

        String[] nodeTypes = { "Rectangle", "Rounded Rect", "Ellipse"};        
        cboNodeType = new JComboBox(nodeTypes);

        lbl = new JLabel("Node Type");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(140, 100, 60, 17);
                
        getContentPane().add(cboNodeType);
        cboNodeType.setBounds(205, 100, 95, 20);
        
        lbl = new JLabel("URL");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(40, 140, 30, 17);
        
        getContentPane().add(tfURL);
        tfURL.setBounds(80, 140, 220, 21);
  
        cbXML.setText("URL is XML");
        getContentPane().add(cbXML);
        cbXML.setBounds(190, 160, 89, 25);
        
        cbLocal.setText("URL is local");
        getContentPane().add(cbLocal);
        cbLocal.setBounds(80, 160, 91, 25);
        
        //cmdBrowse.setText("Browse");
        //cmdBrowse.setMargin(new java.awt.Insets(2, 0, 2, 0));
        //getContentPane().add(cmdBrowse);
        //cmdBrowse.setBounds(250, 140, 50, 21);
        
        lbl = new JLabel("Hint");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(40, 200, 30, 17);

        taHint.setLineWrap(true);
        taHint.setWrapStyleWord(true);
        JScrollPane spHint = new JScrollPane(taHint);
        spHint.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_ALWAYS);        
        getContentPane().add(spHint);
        spHint.setBounds(80, 200, 220, 71);

        getContentPane().add(tfHintWidth);
        tfHintWidth.setBounds(80, 278, 26, 21);

        lbl = new JLabel("Width");
        lbl.setForeground(Color.black);
        getContentPane().add(lbl);
        lbl.setBounds(110, 280, 40, 17);

        getContentPane().add(tfHintHeight);
        tfHintHeight.setBounds(150, 278, 26, 21);

        lbl = new JLabel("Height");
        lbl.setForeground(Color.black);        
        getContentPane().add(lbl);
        lbl.setBounds(180, 280, 40, 17);

        cbHintIsHTML.setText("is HTML");
        getContentPane().add(cbHintIsHTML);
        cbHintIsHTML.setBounds(240, 278, 120, 21);

        Action cancelAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {              
                LBNodeDialog.this.setVisible(false);        
            }
        };

        cmdCancel.setText("Cancel");
        cmdCancel.addActionListener(cancelAction);
        getContentPane().add(cmdCancel);
        cmdCancel.setBounds(130, 320, 80, 20);

        Action okAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {              
                saveChangesAndHideDialog();
            }
        };
        
        cmdOK.setText("OK");
        cmdOK.addActionListener(okAction);
        getContentPane().add(cmdOK);
        cmdOK.setBounds(220, 320, 80, 20);

                
        //pack();
    }
    
    private void initColorSelectors() {
        JLabel lbl; // just a dummy variable        
        
        tfBackColor = new JTextField();
        tfTextColor = new JTextField();
        lblBackColor = new JLabel();
        lblTextColor = new JLabel();
        
        lbl = new JLabel("BackColor");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(-10, 50, 80, 17);

        getContentPane().add(lblBackColor);
        lblBackColor.setBounds(81, 51, 18, 18);        
        lblBackColor.setOpaque(true);
        setBackColor(Color.decode("#A04000"));

        getContentPane().add(tfBackColor);
        tfBackColor.setBounds(105, 50, 60, 21);
        tfBackColor.setHorizontalAlignment(SwingConstants.CENTER);     
        tfBackColor.addKeyListener(new KeyAdapter() {
            public void keyReleased(KeyEvent e) {
                try {
                    Color col = Color.decode("#"+tfBackColor.getText());
                    lblBackColor.setBackground(col);
                    if (lastBackColorButton!=null) lastBackColorButton.setBackground(col);
                } 
                catch (NumberFormatException ex) {}
            }
        });
        
        Action backColorAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {
                lastBackColorButton = (JButton) e.getSource();
                Color c = (lastBackColorButton).getBackground();                  
                setBackColor(c);        
            }
        };
        
        Color[] backColors = new Color[] { 
            Color.decode("#000000"),
            Color.decode("#A04000"),
            Color.decode("#40A000"),
            Color.decode("#0000E0"),
            Color.decode("#707000"),         
            Color.decode("#502070")                               
        };
        
        for (int i=0; i<backColors.length; i++) {
            JButton backColorButton = new JButton(backColorAction);            
            backColorButton.setBackground(backColors[i]);
            getContentPane().add(backColorButton);
            backColorButton.setBounds(170+i*22, 50, 20, 20);
        }        
              
        lbl = new JLabel("Text Color");
        lbl.setHorizontalAlignment(SwingConstants.RIGHT);
        getContentPane().add(lbl);
        lbl.setBounds(0, 71, 70, 17);

        getContentPane().add(lblTextColor);
        lblTextColor.setBounds(81, 72, 18, 18);
        lblTextColor.setOpaque(true);
        setTextColor(Color.white);
                       
        getContentPane().add(tfTextColor);
        tfTextColor.setBounds(105, 71, 60, 21);          
        tfTextColor.setHorizontalAlignment(SwingConstants.CENTER);     
        tfTextColor.addKeyListener(new KeyAdapter() {
            public void keyReleased(KeyEvent e) {
                try {
                    Color col = Color.decode("#"+tfTextColor.getText());
                    lblTextColor.setBackground(col);
                    if (lastTextColorButton!=null) lastTextColorButton.setBackground(col);                    
                } 
                catch (NumberFormatException ex) {}
            }
        });
        
        Action textColorAction = new AbstractAction() {
            public void actionPerformed(ActionEvent e) {
                lastTextColorButton = (JButton) e.getSource();
                Color c = (lastTextColorButton).getBackground();                                  
                setTextColor(c);
            }
        };
        
        Color[] textColors = new Color[] { 
            Color.decode("#FFFFFF"),
            Color.decode("#FF4000"),
            Color.decode("#40FF00"),
            Color.decode("#00FFFF"),
            Color.decode("#FFFF00"),
            Color.decode("#FF00FF")                            
        };

        for (int i=0; i<textColors.length; i++) {
            JButton textColorButton = new JButton(textColorAction);
            textColorButton.setBackground(textColors[i]);
            getContentPane().add(textColorButton);
            textColorButton.setBounds(170+i*22, 71, 20, 20);
        }
    }
    
    private void setBackColor(Color c) {
        lblBackColor.setBackground(c);
        tfBackColor.setText(encodeColor(c));
    }
    
    private void setTextColor(Color c) {
        lblTextColor.setBackground(c);
        tfTextColor.setText(encodeColor(c));
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
        LBNodeDialog nd = new LBNodeDialog(null);
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

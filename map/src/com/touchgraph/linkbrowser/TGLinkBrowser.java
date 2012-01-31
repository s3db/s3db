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
import com.touchgraph.graphlayout.*;
import com.touchgraph.graphlayout.graphelements.GraphEltSet;
import com.touchgraph.graphlayout.interaction.*;
 
import java.io.*;
import java.util.*;
import java.awt.*;
import javax.swing.*;
import javax.swing.text.*;
import javax.swing.text.html.*;
import java.applet.Applet;
import java.applet.AppletContext;
import java.net.MalformedURLException;
import java.net.URL;

import java.awt.event.*;
import edu.stanford.ejalbert.BrowserLauncher;
 
/**  <p><b>TGLinkBrowser:</b> Sets up the basic visual elements of the
  *  TouchGraph LinkBrowser.  Can be run as a stand alone executable, or
  *  an applet.  When run as an executable, editing functionality is enabled.
  *  The applet only allows for browsing the graphs.
  *  
  *  BrowserLauncher is the open source browser launcher used by the TouchGraph LinkBrowser
  *  Copyright 1999-2001 by Eric Albert (ejalbert@cs.stanford.edu)
  *  http://browserlauncher.sourceforge.net/
  * 
  *  @author   Alexander Shapiro                                        
  *  @version  1.20
  */


public class TGLinkBrowser extends JPanel{
    TGPanel tgPanel;

    TGLensSet tgLensSet;
    TGUIManager tgUIManager;
    
    public HVScroll hvScroll;
    public ZoomScroll zoomScroll;
    public LocalityScroll localityScroll;
    public RotateScroll rotateScroll;
    
    public JPopupMenu lbPopup;
    private JMenuBar lbMenuBar;
    final Hashtable scrollBarHash = new Hashtable();
    
    protected LBNodeDialog lbNodeDialog;
    protected LBEdgeDialog lbEdgeDialog;

    JPanel modeSelectPanel; //Made global as a hack to allow hiding edit radio button in applet
    
    XMLio xmlio;
    String xmlFileName;
    String initialNodeName;
    String targetFrame;
    String externalFrame;
    String proxyServlet;

    class UrlAndBase {
        public String url;
        public URL base;    
        public UrlAndBase(String u, URL b) { url = u; base =b; }    
    }
    final Stack graphsVisited = new Stack();
        
    private URL documentBase;
    
    private JApplet enclosingApplet = null; 
    private JFrame enclosingFrame = null;
    
    GraphEltSet completeEltSet;
    
    public TGLinkBrowser() {
        
        completeEltSet = new GraphEltSet();
        tgPanel = new TGPanel();
        tgPanel.setGraphEltSet(completeEltSet);

        xmlio = new XMLio(completeEltSet);                      
        
        tgLensSet = new TGLensSet();                
        hvScroll = new HVScroll(tgPanel, tgLensSet);
        zoomScroll = new ZoomScroll(tgPanel);
        localityScroll = new LocalityScroll(tgPanel);
        rotateScroll = new RotateScroll(tgPanel);
        
        buildPanel();
        
        buildLens();
        tgPanel.setLensSet(tgLensSet);
        
        addUIs();
        setVisible(true);
        
        lbNodeDialog = new LBNodeDialog(tgPanel);
        lbEdgeDialog = new LBEdgeDialog(tgPanel);
         
        tgPanel.add(lbNodeDialog);
        tgPanel.add(lbEdgeDialog);
        LBNode.setNodeBackDefaultColor(Color.decode("#A04000"));
    }
        
    public TGLinkBrowser(JApplet a) {
        this();
        enclosingApplet = a;
        xmlFileName = enclosingApplet.getParameter("initialXmlFile");
        initialNodeName = enclosingApplet.getParameter("initialNode");
        targetFrame =   enclosingApplet.getParameter("targetFrame");
        externalFrame = enclosingApplet.getParameter("externalFrame");
        proxyServlet = enclosingApplet.getParameter("proxyServlet"); 
        if (xmlFileName==null)
            xmlFileName="TouchGraph.xml";
        
        if (targetFrame==null)
            targetFrame="TGLB_target";
        
        if (externalFrame==null)
            externalFrame="main_page";
        
        try {
            documentBase=enclosingApplet.getDocumentBase();
            URL xmlURL = new URL(documentBase, xmlFileName);
            
            if(initialNodeName == null) {
                xmlio.read(xmlURL, new RestoreExactGraph());
            }
            else {
                xmlio.read(xmlURL, new Thread() {
                    public void run() {
                        setLocale(initialNodeName, 2);
                    }
                });
            }
            
        }   
        catch(Exception e) {e.printStackTrace();}        
    }
    
    TGLinkBrowser(JFrame f) {
        this();
        enclosingFrame = f;
        modeSelectPanel.setVisible(true);
        xmlFileName = null;
        
        try {
            documentBase = new File(System.getProperty("user.dir")).toURL();                        
        } catch (Exception e) {}
        
        try {        
            tgPanel.addNode(new LBNode("TouchGraph"));
        }   
        catch (TGException e) {
            e.printStackTrace();
        }
    }

    public TGPanel getTGPanel() {
        return tgPanel;
    }
    
    public URL getDocumentBase() {
        return documentBase;
    }
    
    public void setLocale(String nodeName, int radius) {
        LBNode n = (LBNode) completeEltSet.findNode(nodeName);
        if (n!=null) tgPanel.setLocale(n,radius);
        tgPanel.setSelect(n);
    }

    class HorizontalStretchLens extends TGAbstractLens {
        protected void applyLens(TGPoint2D p) { p.x=p.x*1.5; }
        protected void undoLens(TGPoint2D p) { p.x=p.x/1.5; }
    }
    
    private void buildLens() {
        tgLensSet.addLens(hvScroll.getLens());
        tgLensSet.addLens(zoomScroll.getLens());
        tgLensSet.addLens(rotateScroll.getLens());
        tgLensSet.addLens(new HorizontalStretchLens());
        tgLensSet.addLens(tgPanel.getAdjustOriginLens());       
    }

    private void buildPanel() {        
        final JScrollBar horizontalSB = hvScroll.getHorizontalSB();     
        final JScrollBar verticalSB = hvScroll.getVerticalSB(); 
        final JScrollBar zoomSB = zoomScroll.getZoomSB();
        final JScrollBar rotateSB = rotateScroll.getRotateSB();
        final JScrollBar localitySB = localityScroll.getLocalitySB();
    
        setLayout(new BorderLayout());  
        
        JPanel scrollPanel = new JPanel();
        scrollPanel.setLayout(new GridBagLayout());
        GridBagConstraints c = new GridBagConstraints();

        modeSelectPanel = new JPanel();
        modeSelectPanel.setLayout(new FlowLayout(FlowLayout.CENTER, 0,0)); 
        
        AbstractAction navigateAction = new AbstractAction("Navigate") {
            public void actionPerformed(ActionEvent e) {
                tgUIManager.activate("Navigate");
            }
        };
        
        AbstractAction editAction = new AbstractAction("Edit") {
            public void actionPerformed(ActionEvent e) {                 
                tgUIManager.activate("Edit");
            }
        };
        
        JRadioButton rbNavigate = new JRadioButton(navigateAction);
        rbNavigate.setSelected(true);
        JRadioButton rbEdit = new JRadioButton(editAction);
        ButtonGroup bg = new ButtonGroup();                                                 
        
        bg.add(rbNavigate);
        bg.add(rbEdit);

        modeSelectPanel.add(rbNavigate);
        modeSelectPanel.add(rbEdit);
        
        modeSelectPanel.setVisible(false);
                        
        final JPanel topPanel = new JPanel();
        
        topPanel.setLayout(new GridBagLayout());
        c.gridy=0; c.fill=GridBagConstraints.HORIZONTAL; 
        
        c.gridx=1;c.weightx=0;
        topPanel.add(new BackButton());
        
        c.gridx=1;c.weightx=0;c.insets = new Insets(0,10,0,10);     
        topPanel.add(modeSelectPanel,c);
        
        c.insets=new Insets(0,0,0,0);
        c.gridx=2;c.weightx=1;
        scrollBarHash.put("Zoom", zoomSB);
        scrollBarHash.put("Rotate", rotateSB);
        scrollBarHash.put("Locality", localitySB);
        topPanel.add(scrollSelectPanel(new String[] {"Zoom", "Rotate", "Locality"}),c);
        
        add(topPanel, BorderLayout.NORTH);

        c.fill = GridBagConstraints.BOTH; 
        c.gridwidth = 1;
        c.gridx = 0; c.gridy = 1; c.weightx = 1; c.weighty = 1;
        scrollPanel.add(tgPanel,c);
        
        c.gridx = 1; c.gridy = 1; c.weightx = 0; c.weighty = 0;
        scrollPanel.add(verticalSB,c);
        
        c.gridx = 0; c.gridy = 2;
        scrollPanel.add(horizontalSB,c);
    
        add(scrollPanel,BorderLayout.CENTER);

        lbPopup = new JPopupMenu();
        JMenuItem menuItem = new JMenuItem("Toggle Controls");
        ActionListener toggleControlsAction = new ActionListener() {
                boolean controlsVisible = true;
                public void actionPerformed(ActionEvent e) {
                    controlsVisible = !controlsVisible;
                    horizontalSB.setVisible(controlsVisible);
                    verticalSB.setVisible(controlsVisible);                                     
                    topPanel.setVisible(controlsVisible);
                    if(lbMenuBar!=null) {
                        if(controlsVisible) {
                            enclosingFrame.setJMenuBar(lbMenuBar);                            
                        }
                        else {                            
                            enclosingFrame.setJMenuBar(null);                            
                        }                        
                    }
                }
            };
        menuItem.addActionListener(toggleControlsAction);
        lbPopup.add(menuItem);              
    }
    
    JPanel scrollSelectPanel(String[] scrollBarNames) {
        final JComboBox scrollCombo = new JComboBox(scrollBarNames);
        scrollCombo.setPreferredSize(new Dimension(80,20));
        scrollCombo.setSelectedIndex(0);
        final JScrollBar initialSB = (JScrollBar) scrollBarHash.get(scrollBarNames[0]);
        scrollCombo.addActionListener(new ActionListener() {
            JScrollBar currentSB = initialSB;
            public void actionPerformed(ActionEvent e) {
                JScrollBar selectedSB = (JScrollBar) scrollBarHash.get(
                        (String) scrollCombo.getSelectedItem());
                if (currentSB!=null) currentSB.setVisible(false);
                if (selectedSB!=null) selectedSB.setVisible(true);
                currentSB = selectedSB;
            }
        });
        
        
        final JPanel sbp = new JPanel(new GridBagLayout());
        GridBagConstraints c = new GridBagConstraints();
        c.gridx = 0; c.gridy = 0; c.weightx= 0;
        sbp.add(scrollCombo,c);
        c.gridx = 1; c.gridy = 0; c.weightx = 1; c.insets=new Insets(0,10,0,17);
        c.fill=GridBagConstraints.HORIZONTAL; 
        for (int i = 0;i<scrollBarNames.length;i++) {
            JScrollBar sb = (JScrollBar) scrollBarHash.get(scrollBarNames[i]);
            if(sb==null) continue;
            if(i!=0) sb.setVisible(false);
            sbp.add(sb,c);
        }
        
        return sbp;
    }

    private class BackButton extends JButton {
        BackButton() {
            super("<<< Back");
            setPreferredSize(new Dimension(80,20));
            setMargin(new java.awt.Insets(2, 0, 2, 0));
            this.addActionListener(new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    if(!graphsVisited.empty()) {
                        UrlAndBase lastUandB = (UrlAndBase) graphsVisited.pop();
                        String lastGraph = lastUandB.url;
                        URL lastDocBase = lastUandB.base;
                        try {
                            tgPanel.clearAll();
                            tgPanel.clearSelect();

                            if(lastGraph.substring(0,5).equals("http:")) {                                
                                xmlio.read(new URL(lastGraph), new RestoreExactGraph());
                            }   
                            else {                              
                                //if(enclosingApplet!=null || lastDocBase!=null) {                                                                    
                                    xmlio.read(new URL(lastDocBase,lastGraph), new RestoreExactGraph());
                                //}   
                                //else if(enclosingFrame!=null) {
                                //    xmlio.read(lastGraph, new RestoreExactGraph());
                                //}       
                            }
                            xmlFileName=lastGraph;
                            documentBase=lastDocBase;
                        }   
                        catch(Exception ex) {ex.printStackTrace();}                                                                                                                     
                    }           
                }
            });
        }
    }

    private void addUIs() {
        tgUIManager = new TGUIManager();
        LBEditUI editUI = new LBEditUI(this);
        LBNavigateUI navigateUI = new LBNavigateUI(this);
        tgUIManager.addUI(editUI,"Edit");
        tgUIManager.addUI(navigateUI,"Navigate");
        tgUIManager.activate("Navigate");
    }

    void processNodeUrl(LBNode n) {
      //The urlIsLocal variable can be automatically determined from the prefix, and should probably be eliminated.
      
      if(n!=null && !n.url.trim().equals("")) {
        if(enclosingApplet!=null) {
            String link;
            String target=null;
        
            URL url = null;

            try {
                //urlIsLocal actually means that we want to display it in the target window.
                if (!n.urlIsXML) {
                    if (n.urlIsLocal && !n.url.substring(0,5).equals("http:"))  
                        url = new URL(documentBase, n.url);
                    else
                        url = new URL(n.url);
                }
                else {
                    if (n.urlIsLocal)  
                        url = new URL(documentBase, n.url);
                    else
                        url = new URL(proxyServlet + n.url);                    
                }
            
                if (n.urlIsLocal) 
                    target= targetFrame;
                else
                    target= externalFrame;
                
                System.out.println(url);
                if(!n.urlIsXML) {
                    AppletContext appletcontext = enclosingApplet.getAppletContext();
                    appletcontext.showDocument(url, target);
                }
                else {
                    tgPanel.clearAll();
                    tgPanel.clearSelect();

                    if (xmlFileName!=null)                 
                        graphsVisited.push(new UrlAndBase(xmlFileName,documentBase));
                    
                    xmlFileName = url.toString();
                    if(!n.urlIsLocal) {
                        documentBase = new URL((n.url).substring(0,(n.url).lastIndexOf("/")+1));
                        System.out.println("DocBase: "+documentBase);
                    }
                    try {
                        xmlio.read(url, new RestoreExactGraph());
                    }   
                    catch(Exception ex) {ex.printStackTrace();}                                                                     
                    
                    tgPanel.fireResetEvent();
                    tgPanel.resetDamper();              
                }
            }
            catch(MalformedURLException _ex)
            {
                System.out.println("Malformed URL: " + n.url + " target:" + target);
            }
        }
        else if (enclosingFrame!=null) {    
            if(n.urlIsXML) {                            
                    try {
                        tgPanel.clearAll();
                        tgPanel.clearSelect();
                        
                        if (xmlFileName!=null) 
                            graphsVisited.push(new UrlAndBase(xmlFileName,documentBase));                        
                        
                        xmlFileName=n.url;
                        
                        if(n.urlIsLocal || !n.url.substring(0,5).equals("http:")) 
                            xmlio.read(new URL(documentBase,n.url), new RestoreExactGraph());
                        else {
                            xmlio.read(new URL(n.url), new RestoreExactGraph());
                            documentBase = new URL((n.url).substring(0,(n.url).lastIndexOf("/")));                            
                        }
                    }   
                    catch(Exception ex) { ex.printStackTrace(); }                                                                      
                    tgPanel.fireResetEvent();
                    tgPanel.resetDamper();          
            }
            else {
                System.out.println(n.url);
                try { BrowserLauncher.openURL(n.url); }
                catch (Exception e) { e.printStackTrace(); }
            }
        }
      }
    }
    
   /** Called by LBNodeHintUI when the user clicks a link. */
    public void processHintURL(URL url) { 
        if (enclosingApplet!=null) {
            AppletContext appletcontext = enclosingApplet.getAppletContext();
            appletcontext.showDocument(url, externalFrame);
        }
        else if (enclosingFrame!=null) {
            try { BrowserLauncher.openURL(url.toString()); }
            catch (Exception e) { e.printStackTrace(); }
        }
    }

   /** A thread executed after reading from an XML file, that 
     * sets the visibleLocale to the nodes makred as visible.
     */
    private class RestoreExactGraph extends Thread {                                    
        public void run() {        
            try {                      
               tgPanel.updateLocalityFromVisibility();
            }
            catch (TGException ex) { ex.printStackTrace(); }
               
            tgPanel.clearSelect();
            tgPanel.fireResetEvent();                                                        
            restoreSavedParameters(xmlio.getParameterHash());                            
        }
    }
                  

    /** Creates a Hashtable of parameters to be written out by XMLio */
    private Hashtable createParameterHash() {
        Hashtable params = new Hashtable();
        TGPoint2D offset = hvScroll.getOffset();
        params.put("offsetX",""+(int) offset.x);
        params.put("offsetY",""+(int) offset.y);                    
        params.put("zoomSB",""+zoomScroll.getZoomSB().getValue());                    
        params.put("rotateSB",""+rotateScroll.getRotateSB().getValue());                    
        xmlio.setParameterHash(params);
        return params;
    }

    /** Restores parameters from a hashtable created by XMLio */
    private void restoreSavedParameters(Hashtable params) {     
        TGPoint2D offset = new TGPoint2D(0,0);
        String x_str = (String) params.get("offsetX");
        String y_str = (String) params.get("offsetY");
        if(x_str!=null && y_str!=null) {
            offset.setX(Integer.parseInt(x_str));
            offset.setY(Integer.parseInt(y_str));                                
            hvScroll.setOffset(offset);
        }
        String zoom_str = (String) params.get("zoomSB");
        if(zoom_str!=null) {
            zoomScroll.getZoomSB().setValue(Integer.parseInt(zoom_str));
        }
        String rotate_str = (String) params.get("rotateSB");
        if(rotate_str!=null) {
            rotateScroll.getRotateSB().setValue(Integer.parseInt(rotate_str));
        }
    }
           
    private JMenuBar addMainMenuBar() {
        lbMenuBar = new JMenuBar();
        JMenu fileMenu;
        JMenuItem menuItem;
        
        
        fileMenu = new JMenu("File");
        lbMenuBar.add(fileMenu);

        final JFileChooser chooser = new JFileChooser();
        chooser.setCurrentDirectory(new File(System.getProperty("user.dir")));
        
        menuItem = new JMenuItem("Load");
        ActionListener loadAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {                                       
                    int returnVal = chooser.showOpenDialog(TGLinkBrowser.this);
                    File loadFile;
                    if(returnVal == JFileChooser.APPROVE_OPTION) {
                        loadFile = chooser.getSelectedFile();                                            
                    }
                    else {
                        return;
                    }                    
                                        
                    if (xmlFileName!=null) 
                        graphsVisited.push(new UrlAndBase(xmlFileName,documentBase));                        
                                        
                    xmlFileName = loadFile.getName();
                    try {                        
                        documentBase = new File(loadFile.getParent()).toURL();                          
                    }
                    catch (Exception ex) { ex.printStackTrace(); }
                    
                    tgPanel.clearAll();                                                            
                    try {                        
                        xmlio.read(loadFile.getAbsolutePath(), new RestoreExactGraph());
                    }
                    catch (Exception ex) { ex.printStackTrace(); }
                    
                }
            };
                    
        menuItem.addActionListener(loadAction);
        fileMenu.add(menuItem);
        
        menuItem = new JMenuItem("Save");
        ActionListener saveAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {                                       
                    int returnVal = chooser.showSaveDialog(TGLinkBrowser.this);
                    File saveFile;
                    if(returnVal == JFileChooser.APPROVE_OPTION) {
                        saveFile = chooser.getSelectedFile();                        
                    }
                    else {
                        return;
                    }                    
                                        
                    xmlio.setParameterHash(createParameterHash());
                    
                    try {
                        FileOutputStream saveFileStream = new FileOutputStream(saveFile);
                        xmlio.write(saveFileStream);
                    }
                    catch (Exception ex) { ex.printStackTrace(); }
                }
            };
                    
        menuItem.addActionListener(saveAction);
        fileMenu.add(menuItem);

        menuItem = new JMenuItem("New");
        ActionListener newAction = new ActionListener() {
                public void actionPerformed( ActionEvent e ) {
                    tgPanel.clearAll();
                    tgPanel.clearSelect();
                    try {
                        LBNode firstNode = new LBNode();
                        tgPanel.addNode(firstNode);
                        tgPanel.setSelect(firstNode);
                        lbNodeDialog.setLBNode(firstNode);                                                                    
                        lbNodeDialog.showDialog();
                    } catch ( TGException tge ) {
                        System.err.println(tge.getMessage());
                        tge.printStackTrace(System.err);
                    }
                    tgPanel.fireResetEvent();
                    tgPanel.repaint();
                }
            };
        menuItem.addActionListener(newAction);
        fileMenu.add(menuItem);
        
        menuItem = new JMenuItem("Exit");
        ActionListener exitAction = new ActionListener() {
                public void actionPerformed(ActionEvent e) {
                    System.exit(0);
                }
            };
                    
        menuItem.addActionListener(exitAction);
        fileMenu.add(menuItem);
        
        return lbMenuBar;
    }

    public static void main(String[] args) {        
        JFrame lbFrame;
        lbFrame = new JFrame("TouchGraph LinkBrowser V 1.20");
        TGLinkBrowser lbPanel = new TGLinkBrowser(lbFrame);
        lbFrame.addWindowListener(new WindowAdapter() {
            public void windowClosing(WindowEvent e) {System.exit(0);}
        });
        
        lbFrame.getContentPane().add("Center", lbPanel);

        lbFrame.setJMenuBar(lbPanel.addMainMenuBar());
        
        lbFrame.setSize(500,500);  
        lbFrame.setVisible(true);           
    }
}
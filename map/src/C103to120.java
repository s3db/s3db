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

import com.touchgraph.linkbrowser.LBNode;
import com.touchgraph.linkbrowser.LBEdge;
import com.touchgraph.graphlayout.graphelements.*;
import com.touchgraph.graphlayout.Node;
import com.touchgraph.graphlayout.Edge;
import com.touchgraph.graphlayout.TGException;

import java.io.*;
import java.util.*;
import java.net.*;
import java.awt.*;

import net.n3.nanoxml.*;                              
import java.util.zip.ZipInputStream;
import java.util.zip.GZIPInputStream;

/** <p><b>C103to120:</b> Converts XML files written by V 1.03 of the TouchGraph LinkBrowser 
  *  into those readable by V 1.20.</p>
  *
  *  NanoXML is the open source XML parser used by the TouchGraph LinkBrowser
  *  NanoXML is distrubuted under the zlib/libpng license
  *  Copyrighted (c)2000-2001 Marc De Scheemaecker, All Rights Reserved 
  *  http://nanoxml.sourceforge.net/
  *
  *  @author   Alexander Shapiro
  *  @version  1.20   
  */

public class C103to120 {
	GraphEltSet graphEltSet;
            	
    C103to120() {
		graphEltSet = new GraphEltSet();		
	}
    
    public void read(String fileName) throws Exception {
        InputStream xmlStream = new FileInputStream(fileName);
        
        IXMLParser parser                             
            = XMLParserFactory
                .createDefaultXMLParser();
        
        StdXMLReader reader = new StdXMLReader(xmlStream);
        parser.setReader(reader);
        
        IXMLElement tglbXML = null; 
        try {     
            tglbXML = (IXMLElement) parser.parse();
        } catch (Exception e) {            
            System.out.println("LINE "+reader.getLineNr());
            e.printStackTrace();
        }
        xmlStream.close();
        buildGraphEltSet(tglbXML);    
    }

    private boolean getBooleanAttr(IXMLElement elt, String name, boolean def) {
        String value = elt.getAttribute(name, def? "true": "false");
        return value.toLowerCase().equals("true");
    }

    private String encodeColor(Color c) {
        if (c == null) return null;        
        int rgb = c.getRGB()&0xffffff;
        String zeros = "000000";
        String data = Integer.toHexString(rgb);
        return (zeros.substring(data.length()) + data).toUpperCase();
    }

 
    public void buildGraphEltSet(IXMLElement tglbXML) throws TGException {
      
        IXMLElement nodeSet = 
            (IXMLElement) tglbXML.getChildrenNamed("NODESET").firstElement();
        
        Enumeration nodeEnum = (nodeSet).enumerateChildren();
             
        while (nodeEnum.hasMoreElements()) {
            IXMLElement node = (IXMLElement)(nodeEnum.nextElement());
            String nodeName = node.getAttribute("name"," ");
            String nodeUrl = node.getAttribute("url"," ");
            String nodeHint = node.getAttribute("hint","");
            int x = node.getAttribute("x",0);
            int y = node.getAttribute("y",0);
            int col = node.getAttribute("col",0); //For backward compatibility
            Color color = Color.black;
            try {
                String colorString = node.getAttribute("color","#000000"); 
                color = Color.decode(colorString);
            } 
            catch ( NumberFormatException ex) {};
            
            boolean urlIsLocal = getBooleanAttr(node, "urlIsLocal", false); 
            boolean urlIsXML = getBooleanAttr(node, "urlIsXML", false);  
            String nodeID = node.getAttribute("nodeID",null);
            if (nodeID==null) 
                throw new TGException(TGException.NODE_NO_ID, "node has no ID");
             
            LBNode newNode = new LBNode(nodeID, nodeName, nodeUrl);
            newNode.setVisible(true);
            newNode.setURLIsLocal(urlIsLocal);
            newNode.setURLIsXML(urlIsXML);
            newNode.setLocation(new Point(x,y));            
            newNode.setHint(nodeHint);
            
            
            switch (col) {
                case 0:  newNode.setBackColor(color); break;
                case 1:  newNode.setBackColor(new Color(160, 64, 0)); break;
                case 2:  newNode.setBackColor(new Color(64, 160, 0)); break;
                case 3:  newNode.setBackColor(new Color(0, 0, 224)); break;
                default:  newNode.setBackColor(color); break;
            }   
                       
            graphEltSet.addNode(newNode);
        }

        IXMLElement edgeSet = 
            (IXMLElement) tglbXML.getChildrenNamed("EDGESET").firstElement();
        
        Enumeration edgeEnum = (edgeSet).enumerateChildren();
                    
        while (edgeEnum.hasMoreElements()) {
            IXMLElement edge = (IXMLElement)(edgeEnum.nextElement());
            String fromID = edge.getAttribute("fromID",null);
            String toID = edge.getAttribute("toID",null);
            int tension = edge.getAttribute("tension", 4000); //For backward compatibility
            int length = edge.getAttribute("length", 4000);
            int edgeType = edge.getAttribute("type", 1);
            if (length ==0) length= tension;
            Color color= Edge.DEFAULT_COLOR;
            try {
                String colorString = edge.getAttribute("color",null); 
                if (colorString!=null) color = Color.decode(colorString);                
            } 
            catch ( NumberFormatException ex) {};
            LBNode fromNode = (LBNode) graphEltSet.findNode(fromID);
            LBNode toNode = (LBNode) graphEltSet.findNode(toID);
            LBEdge newEdge = new LBEdge(fromNode,toNode, length/100);
            newEdge.setVisible(true);
            newEdge.setColor(color); 
            
            switch (edgeType) {
                case 0:  newEdge.edgeType = LBEdge.BIDIRECTIONAL_EDGE; break;
                case 1:  newEdge.edgeType = LBEdge.HIERARCHICAL_EDGE; break;
                default: newEdge.edgeType = LBEdge.BIDIRECTIONAL_EDGE; break;
            }
            graphEltSet.addEdge(newEdge);
        }                
    }
	
    public void write(String fileName) throws Exception {
        OutputStream out = new FileOutputStream(fileName);
        
        XMLElement tglbXML = new XMLElement("TOUCHGRAPH_LB");
        tglbXML.setAttribute("version", "1.20");
        
        final XMLElement nodeSet = new XMLElement("NODESET");                
        tglbXML.addChild(nodeSet);
                
        TGForEachNode fen = new TGForEachNode() {
            public void forEachNode( Node node ) {
                LBNode lbNode = (LBNode) node;
                XMLElement nodeElt = new XMLElement("NODE");
                nodeElt.setAttribute("nodeID", lbNode.getID());
                
                XMLElement nodeLocation = new XMLElement("NODE_LOCATION");
                nodeLocation.setAttribute("x", ""+(int) lbNode.getLocation().getX());
                nodeLocation.setAttribute("y", ""+(int) lbNode.getLocation().getY());
                nodeLocation.setAttribute("visible", lbNode.isVisible() ? "true" : "false");
                nodeElt.addChild(nodeLocation);
                
                XMLElement nodeLabel = new XMLElement("NODE_LABEL");
                nodeLabel.setAttribute("label", lbNode.getLabel());
                nodeLabel.setAttribute("shape", ""+lbNode.getType());
                nodeLabel.setAttribute("backColor", encodeColor(lbNode.getBackColor()));
                nodeLabel.setAttribute("textColor", encodeColor(lbNode.getTextColor()));
                nodeLabel.setAttribute("fontSize", ""+lbNode.getFont().getSize());
                nodeElt.addChild(nodeLabel);
                
                XMLElement nodeURL = new XMLElement("NODE_URL");
                nodeURL.setAttribute("url", lbNode.getURL());
                nodeURL.setAttribute("urlIsLocal", lbNode.getURLIsLocal() ? "true" : "false");
                nodeURL.setAttribute("urlIsXML", lbNode.getURLIsXML() ? "true" : "false");
                nodeElt.addChild(nodeURL);
                
                XMLElement nodeHint = new XMLElement("NODE_HINT");
                nodeHint.setAttribute("hint", lbNode.getHint());
                nodeHint.setAttribute("width", ""+lbNode.getHintWidth());
                nodeHint.setAttribute("height", ""+lbNode.getHintHeight());
                nodeHint.setAttribute("isHTML", lbNode.getHintIsHTML() ? "true" : "false");
                nodeElt.addChild(nodeHint);
                                
                nodeSet.addChild(nodeElt);
            }
        };

        graphEltSet.forAllNodes(fen);
        
        final XMLElement edgeSet = new XMLElement("EDGESET");
        tglbXML.addChild(edgeSet);

        TGForEachEdge fee = new TGForEachEdge() {
            public void forEachEdge( Edge edge ) {
                LBEdge lbEdge = (LBEdge) edge;
                XMLElement edgeElt = new XMLElement("EDGE");                
                edgeElt.setAttribute("fromID", lbEdge.getFrom().getID());
                edgeElt.setAttribute("toID", lbEdge.getTo().getID());
                edgeElt.setAttribute("type", ""+lbEdge.getType());
                edgeElt.setAttribute("length", ""+(int) lbEdge.getLength());                              
                edgeElt.setAttribute("visible", lbEdge.isVisible() ? "true" : "false");
                edgeElt.setAttribute("color", encodeColor(lbEdge.getColor()));                
                edgeSet.addChild(edgeElt);
            }
        };

        graphEltSet.forAllEdges(fee);
        
        
        XMLElement parameters = new XMLElement("PARAMETERS");
        tglbXML.addChild(parameters);
                
        InputStream prependDTD = getClass().getResourceAsStream("/TG_Prepend_DTD.xml");
                
        int c; //Slow, but gets the job done for small files
        while((c = prependDTD.read())!=-1) {
            out.write(c);
        }
        
        prependDTD.close();
        
        XMLWriter writer                      
            = new XMLWriter(out);
        writer.write(tglbXML, true);
        out.close();
    }
    
    public static void main(String[] args) {            
        if (args.length != 2) {
            System.out.println("Usage: C103to120 source_file destination_file");
            return;
        }
        C103to120 convert = new C103to120();
        try {
            convert.read(args[0]);
            convert.write(args[1]);
        }
        catch (Exception e) { e.printStackTrace(); }
    }
}


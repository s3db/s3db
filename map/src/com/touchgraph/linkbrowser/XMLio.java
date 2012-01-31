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

/** <p><b>XMLio:</b> Allows for reading and writing of TouchGraph Link Browser XML files .</p>
  *
  *  NanoXML is the open source XML parser used by the TouchGraph LinkBrowser
  *  NanoXML is distrubuted under the zlib/libpng license
  *  Copyrighted (c)2000-2001 Marc De Scheemaecker, All Rights Reserved 
  *  http://nanoxml.sourceforge.net/
  *
  *  @author   Alexander Shapiro
  *  @version  1.20   
  */


   
public class XMLio {
	GraphEltSet graphEltSet;
            
    Hashtable parameterHash;  //parameterHash is used to record non graph-structure data, 
                              //like the position of the scrollbars. 
                              //Needs to be reset before writing, or old parameters will be written.
	
	XMLio(GraphEltSet ges) {
		graphEltSet = ges;
		parameterHash = new Hashtable();
	}

    public void setParameterHash( Hashtable ph ) {
        parameterHash = ph;  
    }
	
    public Hashtable getParameterHash() {
        return parameterHash;  
    }

	public void read(String fileName) throws Exception { 
		read(fileName, null);
	}

    public void read(URL xmlURL) throws Exception { 
        read(xmlURL, null);
    }   
    
    public void read(String fileName, Thread afterReading) throws Exception {
        read(fileName, new FileInputStream(fileName), afterReading);        
	}

   /** Reads data from a URL <tt>url</tt>, executing the <tt>afterReading</tt> Thread
     * after the data is read in.
     */
    public void read(URL url, Thread afterReading) throws Exception{
        URLConnection xmlConn = url.openConnection();
        // Filtering the data from the URL through the Specified File Size Input Stream
        // is necessary because there can be transfer delays, and the parser fails if
        // read() methods return empty.
        //
        // xmlConn.getContentLength() returns -1 if the content length is unspecified,
        // which is conveniently equal to SFSInputStream.INFINITE_FS
        SFSInputStream sfsInputStream = 
              new SFSInputStream(xmlConn.getInputStream(), xmlConn.getContentLength());
        
        read(url.toString(),sfsInputStream,afterReading);
    }

    public void read(String fileName, InputStream in, Thread afterReading) throws Exception {
        InputStream xmlStream;
        if (fileName.toLowerCase().endsWith(".zip")) { 
            xmlStream = new ZipInputStream(in);
            ((ZipInputStream) xmlStream).getNextEntry();
        }
        else if (fileName.toLowerCase().endsWith(".gz")) {
            xmlStream = new GZIPInputStream(in);
        }
        else {
            xmlStream = in;
        }

        //IXMLParser parser                             
        //    = XMLParserFactory
        //        .createDefaultXMLParser();
        IXMLParser parser = new StdXMLParser();
        parser.setBuilder(new StdXMLBuilder());
        parser.setValidator(new NonValidator());
        
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
        buildGraphEltSet(tglbXML, afterReading);    
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

    private void buildGraphEltSet(IXMLElement tglbXML, Thread afterReading) throws TGException {
                   
        IXMLElement nodeSet = 
            (IXMLElement) tglbXML.getChildrenNamed("NODESET").firstElement();
        
        Enumeration nodeEnum = (nodeSet).enumerateChildren();
			 
        while (nodeEnum.hasMoreElements()) {
	       	IXMLElement node = (IXMLElement)(nodeEnum.nextElement());
            
            String nodeID = node.getAttribute("nodeID",null);
            if (nodeID==null) 
                throw new TGException(TGException.NODE_NO_ID, "node has no ID");             
            LBNode newNode = new LBNode(nodeID, "", "");
                                   
            Vector v;
            v = node.getChildrenNamed("NODE_LOCATION");            
            if(!v.isEmpty()) {
                IXMLElement nodeLocation = (IXMLElement) v.firstElement();
                int x = nodeLocation.getAttribute("x",0);
                int y = nodeLocation.getAttribute("y",0);                    
                newNode.setLocation(new Point(x,y));
                newNode.setVisible(getBooleanAttr(nodeLocation, "visible", false)); 
            }
            
            v = node.getChildrenNamed("NODE_LABEL");            
            if(!v.isEmpty()) {
                IXMLElement nodeLabel = (IXMLElement) v.firstElement();
                
                newNode.setLabel(nodeLabel.getAttribute("label"," "));
                newNode.setType(nodeLabel.getAttribute("shape",0));
                
                try {
                    newNode.setBackColor(
                           Color.decode("#"+nodeLabel.getAttribute("backColor","000000")));
                } catch ( NumberFormatException ex) {};
                
                try {
                    newNode.setTextColor(
                           Color.decode("#"+nodeLabel.getAttribute("textColor","000000")));
                } catch ( NumberFormatException ex) {};
                
                int fontSize = nodeLabel.getAttribute("fontSize",12);
                newNode.setFont(new Font(LBNode.TEXT_FONT.getFamily(), Font.PLAIN, fontSize ));
            }
            
            v = node.getChildrenNamed("NODE_URL");            
            if(!v.isEmpty()) {
                IXMLElement nodeURL = (IXMLElement) v.firstElement();                
                newNode.setURL(nodeURL.getAttribute("url",""));
                newNode.setURLIsLocal(getBooleanAttr(nodeURL, "urlIsLocal", false));                                 
                newNode.setURLIsXML(getBooleanAttr(nodeURL, "urlIsXML", false));  
            }

            v = node.getChildrenNamed("NODE_HINT");            
            if(!v.isEmpty()) {
                IXMLElement nodeHint = (IXMLElement) v.firstElement();                
                newNode.setHint(nodeHint.getAttribute("hint",""));                
                newNode.setHintWidth(nodeHint.getAttribute("width",300));                                 
                newNode.setHintHeight(nodeHint.getAttribute("height",-1));  
                newNode.setHintIsHTML(getBooleanAttr(nodeHint, "isHTML", false));  
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
           	int length = edge.getAttribute("length", 4000);
              
            LBNode fromNode = (LBNode) graphEltSet.findNode(fromID);
            LBNode toNode = (LBNode) graphEltSet.findNode(toID);
            LBEdge newEdge = new LBEdge(fromNode,toNode, length);
           	           	
           	Color color = LBEdge.DEFAULT_COLOR;
 			try {
 				String colorString = edge.getAttribute("color","000000"); 
                color = Color.decode("#"+colorString);
 			} 
 			catch ( NumberFormatException ex) {};      		
      		newEdge.setColor(color);
                newEdge.setLabel(edge.getAttribute("label"," "));
 
            int edgeType = edge.getAttribute("type", 0);
      		switch (edgeType) {
            	case 0:  newEdge.setType(LBEdge.BIDIRECTIONAL_EDGE); break;
                case 1:  newEdge.setType(LBEdge.HIERARCHICAL_EDGE); break;
                default: newEdge.setType(LBEdge.BIDIRECTIONAL_EDGE); break;
			}
			
            newEdge.setVisible(getBooleanAttr(edge, "visible", false));
			graphEltSet.addEdge(newEdge);						
        }
        
        parameterHash.clear(); //Clear out old data before reading
        
        Vector paramV = tglbXML.getChildrenNamed("PARAMETERS");
        if (paramV!=null && !paramV.isEmpty()) {
            IXMLElement parameters = 
                (IXMLElement) paramV.firstElement();

            Enumeration paramEnum = (parameters).enumerateChildren();
            while (paramEnum.hasMoreElements()) {
                IXMLElement param = (IXMLElement)(paramEnum.nextElement());
                String name = param.getAttribute("name",null);
                String value = param.getAttribute("value",null);
                if(name!=null) parameterHash.put(name,value);
            }            
        }
		if (afterReading!=null) afterReading.start();        
	}
	
    public void write(OutputStream out) throws Exception {
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
                edgeElt.setAttribute("label", lbEdge.getLabel());
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
        
        Enumeration paramNames = parameterHash.keys();
        while (paramNames.hasMoreElements()) {
            String name = (String) paramNames.nextElement();
            String value = (String) parameterHash.get(name);
            XMLElement param = new XMLElement("PARAM");
            param.setAttribute("name", name);
            param.setAttribute("value", value);
            parameters.addChild(param);
        }                  

        InputStream prependDTD = getClass().getResourceAsStream("/TG_Prepend_DTD.xml");
        
        /* Appends a mysterious empty string to the end to the TG_Prepend_DTD.xml
        int avail;
        while ((avail = prependDTD.available())!=0) {
            byte b[] = new byte[avail];
            prependDTD.read(b);
            out.write(b);
        }
        */
        
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
}


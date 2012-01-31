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
import java.io.*;
import java.awt.*;

/**  LBEdge:  A LinkBrowser Edge.  Extends edge by adding different edge types, specifically a bidirectional
  *  edge type rendered as a thin line.  
  *
  *  Modified by Chuming chen on Dec. 07, 2004 to add edge label 
  *
  *  @author   Alexander Shapiro                                        
  *  @author   Chuming Chen                                        
  *  @version  1.20
  */

public class LBEdge extends com.touchgraph.graphlayout.Edge {
    public final static int BIDIRECTIONAL_EDGE=0;
    public final static int HIERARCHICAL_EDGE=1;
    
    public static int DEFAULT_TYPE = 1;
    
    public int edgeType;
    
    public static Font HINT_FONT = new Font("Courier",Font.PLAIN,11);
    public static Color HINT_BACK_COLOR = Color.darkGray;
    public static Color HINT_TEXT_COLOR = Color.white;    
    public static int MINIMUM_LABEL_HEIGHT = 20;
   // public static int drawx;
    //public static int drawy;                                                                                
    String url;
    boolean urlIsLocal=false;
    boolean urlIsXML=false;
    String label;
    boolean labelIsHTML=false;
    int labelWidth = 300;
    int labelHeight = -1; //A value of less then MINIMUM_LABEL_HEIGHT means that the label height
                         //is automatically determined
    Font labelFont;

    
    public LBEdge(LBNode f, LBNode t) {
        this(f, t, DEFAULT_LENGTH);
      //  edgeHintPosition(); 
	}	

    public LBEdge(LBNode f, LBNode t, int len) {
        super(f,t,len);
        edgeType = DEFAULT_TYPE;
       // edgeHintPosition(); 
	}	


    public LBEdge(LBNode f, LBNode t, int len, String l, String u) {
        super(f,t,len);
        //edgeHintPosition(); 
        edgeType = DEFAULT_TYPE;
        url = u;
        label =l;
        labelFont = HINT_FONT;
        
	}	
    public void setType(int t) { 
        edgeType = t;
    }
   /* 
    private void edgeHintPosition() {
	//System.out.println(this.getFrom().getLocation().x + " : " + this.getFrom().getLocation().y);
	//System.out.println(this.getTo().getLocation().x + " : " + this.getTo().getLocation().y);
	if(this.getFrom().getLocation().x <= this.getTo().getLocation().x) {
		drawx = (int)this.getFrom().getLocation().x + Math.abs((int)(this.getFrom().getLocation().x - this.getTo().getLocation().x))/2;	
        }
	else {
		drawx = (int)this.getTo().getLocation().x + Math.abs((int)(this.getFrom().getLocation().x - this.getTo().getLocation().x))/2;	
	}
        if(this.getFrom().getLocation().y <= this.getTo().getLocation().y) {
		drawy = (int)this.getFrom().getLocation().y -  Math.abs((int)(this.getFrom().getLocation().y - this.getTo().getLocation().y))/2;	
        }
        else {
		drawy = (int)this.getTo().drawy -  Math.abs((int)(this.getFrom().drawy - this.getTo().drawy))/2;	

	}
    }
 */ 
    public static void setEdgeDafaultType(int type) { DEFAULT_TYPE = type; }
        
    public int getType() { 
        return edgeType;
    }
    
    public void setEdgeLabelFont( Font font) { HINT_FONT = font; }
    public void setEdgeLabelBackColor( Color color ) { HINT_BACK_COLOR = color; }
    public void setEdgeLabelTextColor( Color color ) { HINT_TEXT_COLOR = color; }

    public void setURL(String u) {
        url = u;
    }
                                                                                
    public String getURL() {
        return url;
    }
                                                                                
    public void setLabel(String l) {
        label = l;
    }
                                                                                
    public String getLabel() {
        return label;
    }
                                                                                
    public void setLabelIsHTML(boolean hih) {
        labelIsHTML = hih;
    }

    public boolean getLabelIsHTML() {
        return labelIsHTML;
    }
                                                                                
    public void setLabelWidth(int lw) {
        labelWidth = lw;
    }
                                                                                
    public int getLabelWidth() {
        return labelWidth;
    }
                                                                                
    public void setLabelHeight(int lh) {
        labelHeight = lh;
    }
                                                                                
    public int getLabelHeight() {
        return labelHeight;
    }
    
    public void setURLIsLocal(boolean uil) {
        urlIsLocal = uil;
    }
                                                                                
    public boolean getURLIsLocal() {
        return urlIsLocal;
    }
                                                                                
    public void setURLIsXML(boolean uix) {
        urlIsXML = uix;
    }
                                                                                
    public boolean getURLIsXML() {
        return urlIsXML;
    }
/*    
    public int getWidth() {
        if(fontMetrics!=null && lbl!=null) {
            if(typ!=TYPE_ELLIPSE)
                return fontMetrics.stringWidth(h) + 8;
            else
                return fontMetrics.stringWidth(h) + 28;
        }
        else
            return 8;
    }
    
     public int getHeight() {
        if (fontMetrics!=null)
            return fontMetrics.getHeight()+2;
        else
            return 8;
    }

*/
	public static void paintFatLine(Graphics g, int x1, int y1, int x2, int y2, Color c) {  
	    g.setColor(c);  
        g.drawLine(x1,   y1,   x2,   y2);
        g.drawLine(x1+1, y1,   x2+1, y2);
        g.drawLine(x1+1, y1+1, x2+1, y2+1);
        g.drawLine(x1,   y1+1, x2  , y2+1);
	}

    public static void paint(Graphics g, int x1, int y1, int x2, int y2, Color c, int type) {
        switch (type) {
            case BIDIRECTIONAL_EDGE:   paintFatLine(g, x1, y1, x2, y2, c); break;
            case HIERARCHICAL_EDGE:  paintArrow(g, x1, y1, x2, y2, c);  break;
        }       
    }

	public void paint(Graphics g, TGPanel tgPanel) {
        Color c;
        
        if (tgPanel.getMouseOverN()==from || tgPanel.getMouseOverE()==this) 
            c = MOUSE_OVER_COLOR; 
        else
            c = col;        

		int x1=(int) from.drawx;
		int y1=(int) from.drawy;
		int x2=(int) to.drawx;
		int y2=(int) to.drawy;
		if (intersects(tgPanel.getSize())) {
            paint(g, x1, y1, x2, y2, c, edgeType);
		}
	}	
}

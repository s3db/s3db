                         *****************************
                         *                           *
                         *   TouchGraph LinkBrowser  *	
                         *          V 1.20           *
                         *      (c) 2001-2002        *
                         *     Alexander Shapiro,    *
                         *       TouchGraph LLC      *
                         *                           *
                         *****************************

     You are welcome to use the TouchGraph LinkBrowser free of charge.
          If you use the LinkBrowser as an applet, please provide 
                         a link back to touchgraph.com

               If you have any questions, comments, or suggestions, 
                        send mail to alex@touchgraph.com

================================================================================

Included in this archive you'll find the following files:

TGLinkBrowser.bat:  The bach file which launches the client-based LinkBrowser.
  TGLinkBrowser.jar, nanoxml-2.1.1.jar, and BrowserLauncher.jar must be in the
  same directory as TGLinkBrowser.bat

TGLinkBrowser.html:  The html file which launches the LinkBrowser as an applet.
  For version 1.20, the same three jar files required for the client side
  version, need to be placed on the server in the same directory as 
  TGLinkBrowser.html. Future versions of the LinkBrowser will feature a slimmed 
  down TGLinkBrowser.jar and will not require BrowserLauncher.jar for the applet
  version.

C103to120.bat:  A utility for converting files created with the V1.03 version of
  the LinkBrowser into ones readable by V1.20.  TouchGraph_LB.dtd, which was
  included in the V1.03 release must be in the same path as the XML file being
  converted.  TGLinkBrowser.jar, nanoxml-2.1.1.jar must be in the same directory
  as C103to120.bat 

TGLinkBrowser.jar:  The archive containing the code for the TouchGraph 
  LinkBrowser.

nanoxml-2.1.1.jar:  The archive containing the code for NanoXML, a compact XML
  parser, Copyright 2000-2001 Marc De Scheemaecker, nanoxml.sourceforge.net 

BrowserLauncher.jar:  The archive of a utility for launching external  
  browsers, Copyright 1999-2001 Eric Albert, browserlauncher.sourceforge.net

InitialXML.xml:  A sample XML file containing a graph that demonstrates some
  of the LinkBrowser's functionality.  To view, select file->load:
  InitialXML.xml from the client-based version.  TGLinkBrowser.html is set to
  load this file automatically.  

SmallWingedTriangle.jpg:  The current TouchGraph logo, referenced by
  InitialXML.xml as a demonstration of diplaying pictures in hints.

HintFrameExample.html:  An html file referenced by InitialXML.xml as a
  demonstration of displaying external html files in hints.

proxy\Proxy.pl, Proxy.class:  A perl script and a java servlet used for loading
  remote XML files when running the LinkBrowser as an applet.  
  Written by Victor Volle <v.volle@computer.org>

ReleaseNotes.txt:  Release notes for version 1.20 of the TouchGraph LinkBrowser.

ReadMe.txt:  This File

================================================================================

Contributors

  Murray Altheim:  http://www.altheim.com/murray/

  Victor Volle: victor.volle@artive.de

This software includes code from:

  NanoXML, a compact XML parser, 
  Copyright 2000-2001 Marc De Scheemaecker
  nanoxml.sourceforge.net 

  BrowserLauncher, a utility for launching external browsers, 
  Copyright 1999-2001 Eric Albert
  browserlauncher.sourceforge.net

  Sun's graph layout applet:
  java.sun.com/applets/jdk/1.2/demo/applets/GraphLayout/example1.html

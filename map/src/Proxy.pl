#!/usr/local/bin/perl -w
#

#-----------------------------------------------
#  Proxy Servlet.  
#
#  Author:  Victor Volle  victor.volle@artive.de                                      
#  Version: 1.00
#-----------------------------------------------

# First, get the CGI variables into a list of strings
%cgivars= &getcgivars ;


require LWP::UserAgent;
$ua = new LWP::UserAgent;

$request = new HTTP::Request('GET', $cgivars{"URL"});

$response = $ua->request($request); # or

if ( $response->is_success )
{
    print "Content-type: ", $response->content_type, "\n\n";
    binmode STDOUT;
    print $response->content;
}
else
{
   
    # Print the CGI response header, required for all HTML output
    # Note the extra \n, to send the blank line
    print "Content-type: text/html\n\n" ;

    print $response->protocol(), " ", $response->status_line, "\n";
    

    

    exit ;

}

#----------------- start of &getcgivars() module ----------------------

# Read all CGI vars into an associative array.
# If multiple input fields have the same name, they are concatenated into
#   one array element and delimited with the \0 character (which fails if
#   the input has any \0 characters, very unlikely but conceivably possible).
# Currently only supports Content-Type of application/x-www-form-urlencoded.
sub getcgivars {
    local($in, %in) ;
    local($name, $value) ;


    # First, read entire string of CGI vars into $in
    if ( ($ENV{'REQUEST_METHOD'} eq 'GET') ||
         ($ENV{'REQUEST_METHOD'} eq 'HEAD') ) {
        $in= $ENV{'QUERY_STRING'} ;

    } elsif ($ENV{'REQUEST_METHOD'} eq 'POST') {
        if ($ENV{'CONTENT_TYPE'}=~ m#^application/x-www-form-urlencoded$#i) {
            $ENV{'CONTENT_LENGTH'}
                || &HTMLdie("No Content-Length sent with the POST request.") ;
            read(STDIN, $in, $ENV{'CONTENT_LENGTH'}) ;

        } else { 
            &HTMLdie("Unsupported Content-Type: $ENV{'CONTENT_TYPE'}") ;
        }

    } else {
        &HTMLdie("Script was called with unsupported REQUEST_METHOD.") ;
    }
    
    # Resolve and unencode name/value pairs into %in
    foreach (split('&', $in)) {
        s/\+/ /g ;
        ($name, $value)= split('=', $_, 2) ;
        $name=~ s/%(..)/chr(hex($1))/ge ;
        $value=~ s/%(..)/chr(hex($1))/ge ;
        $in{$name}.= "\0" if defined($in{$name}) ;  # concatenate multiple vars
        $in{$name}.= $value ;
    }

    return %in ;

}


# Die, outputting HTML error page
# If no $title, use a default title
sub HTMLdie {
    local($msg,$title)= @_ ;
    $title || ($title= "CGI Error") ;
    print <<EOF ;
Content-type: text/html

<html>
<head>
<title>$title</title>
</head>
<body>
<h1>$title</h1>
<h3>$msg</h3>
</body>
</html>
EOF

    exit ;
}
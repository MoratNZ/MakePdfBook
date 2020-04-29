#! /usr/bin/env perl
use strict;

=pod

=head1 NAME

makePdfBook.pl

=head1 SYNOPSIS

makePdfBook.pl directoryName outputFileName

=over 8

=item B<directoryName>

The directory that the source files are in.
This should contain one or more files labelled chapter-1.html to chapter-X.html, and a template.tex file that lays out the structure for the pdf.
It may also optionally contain a titlepage.html file, containing a titlepage section, which will be inserted ahead of the table of contents.

=item B<outputFileName>

The filename, including full path, where you would like the output PDF file placed.

=back

=head1 DESCRIPTION
This script takes a directory containing one or more HTML files, and a tex template file, and outputs a PDF file.
The HTML files must be labelled C<chapter-NUMERAL.html>, e.g., C<chapter-1.html>, and will be included in the pdf in numerical order.
The template file must be labelled C<template.tex>.
Optionall, a titlepage file labelled C<titlepage.html> can be included. If it's included, it will be appended to the beginning of the PDF, ahead of the table of contents.



=head1 AVAILABILITY

https://github.com/MoratNZ/MakePdfBook

=head1 AUTHOR

David Maclagan

=head1 ACKNOWLEDGEMENTS

This was inspired by, and springboarded off, the work of Aran Dunkley on the MediaWiki PdfBook extension.

=head1 LICENSE

MIT

=cut

# Grab the command line arguments
my $directoryName = shift;
my $outputFile = shift;

# Check that the directory exists, and is a directory
if(!(-e $directoryName && -d $directoryName)){
    print "Error: $directoryName does not exist, or is not a directory\n";
    exit 1;
}
if(!(-R $directoryName && -W $directoryName)){
    print "Error: $directoryName must be both readable and writable\n";
    exit 1;
}
chdir $directoryName;

# Check whether the template file is present
if( ! -e "template.tex"){
    print "Error: template file $directoryName/template.tex does not exist\n";
    exit 1;
}

# Check whether a titlepage is present
my $titlepageFile;

if( -e "titlepage.html"){
    $titlepageFile = "titlepage.html";
} 

# Grab a list of all the chapters and sort them
my @chapters = <"chapter-*.html">;

@chapters = sort {substr($a, 8) <=> substr($b, 8)} @chapters;

# If there is a titlepage, convert the HTML to tex
my $cmd;
my $titleOption;
my $result;
if($titlepageFile){
    $cmd = "PATH=/usr/bin/: pandoc titlepage.html -o titlepage.tex 2>&1";
    $result = `$cmd`;
    if($result){
        if( $result =~ /Cannot decode byte '(.*)'/){
            my $badByte = $1;
            print "Error converting titlepage to tex.\n".
            "There is a special character (utf character code [$badByte]) that pandoc has trouble with\n".
            "Unfortunately, you'll need to track it down and remove it.";
        } else {
            print "Error converting titlepage to tex - $result\n";
        }
        exit 1;
    }
    $titleOption = " --include-before-body titlepage.tex "
} else {
    $titleOption = "";
}

# Build the tex file for the body of the book
my $sourceFileString = join(" ", @chapters);
$cmd = "PATH=/usr/bin/: pandoc  -f html -t latex --template template.tex $titleOption -o book.tex $sourceFileString  2>&1";
$result = `$cmd`;

if($result){
    if( $result =~ /Cannot decode byte '(.*)'/){
        my $badByte = $1;
        print "Error producing initial tex file for book.\n".
        "There is a special character (utf character code [$badByte]) that pandoc has trouble with\n".
        "Unfortunately, you'll need to track it down and remove it.\n";
    } else {
        print "Error producing initial tex file for book - $result\n";
    }
    exit 1;
}

# Modify the tex output, to account for pandoc's suboptimal preferences

# Generate the PDF from the modified tex 
$cmd = "PATH=/usr/bin/: pdflatex -halt-on-error -jobname temp book.tex 1>/dev/null";

$result = `$cmd`; # creates the PDF with an empty TOC
print $result;
$result = `$cmd`; # Now there's a TOC, but the page numbers are wrong
print $result;
$result = `$cmd`; # Now the page numbers are right
print $result;

# Move the generated PDF to the output location

rename "temp.pdf", $outputFile;




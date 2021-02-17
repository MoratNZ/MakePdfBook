#! /usr/bin/env perl
use strict;

=pod

=head1 NAME

makePdfBook.pl

=head1 SYNOPSIS

makePdfBook.pl directoryName outputFileName
Outputs only on error.

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
    # wrap some tex around the titlepage, to make it titlepagier

    my $prepends = "\\setcounter{secnumdepth}{0}\n\\thispagestyle{empty}";
    my $postpends = "\\setcounter{secnumdepth}{3}";

    open my $in, '<:encoding(utf8)','titlepage.tex' or die("Error: couldn't open titlepage.tex for reading - $!");
    local $/ = undef;
    my $content = <$in>;
    close $in;

    $content =~ s/\\%pagebreak\\%/\\newpage\n/ig;
    $content =~ s/\\%startHuge\\%/\n\\Huge\n/ig;
    $content =~ s/\\%startCenter\\%/\\begin{center}\n/ig;
    $content =~ s/\\%endHuge\\%/\\normalsize\n/ig;
    $content =~ s/\\%endCenter\\%/\\end{center}\n/ig;

    $content =~ s/\\%vspace\\%(\d+?)\\%/\\vspace{\1mm}\n/ig;
    
    open my $out, '>:encoding(utf8)', 'titlepage.tex' or die("Error: couldn't open titlepage.tex for writing - $!");
    printf $out "%s\n%s\n%s", 
        $prepends,
        $content,
        $postpends;
    close $out;

    $titleOption = " --include-before-body titlepage.tex "
} else {
    $titleOption = "";
}

# Build the tex file for the body of the book
my $sourceFileString = join(" ", @chapters);
my $templateFile = "template.tex"
$cmd = "PATH=/usr/bin/: pandoc  -f html -t latex --template $templateFile $titleOption -o book.original.tex $sourceFileString  2>&1";
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
#
# This next chunk is crude brute-force regex mangling. 
# And the reason this work was spun out to a perl script rather than kept in php or 
# farmed out to a civilised language like python

open my $in, '<:encoding(utf8)','book.original.tex' or die("Error: couldn't open book.original.tex for reading - $!");
my @lines = <$in>;
close $in;

my $lineCount = scalar(@lines);

for(my $i = 0; $i < $lineCount; $i++){
    my $line = $lines[$i];
    if($line =~ /^\s*\\begin\{longtable\}\[\]\{@\{\}(.*)@\{\}\}$/){
        #\begin{longtable}[]{@{}l@{}}
        my $columndefs = $1;
        my $count = length($columndefs);
        #$count += 1;
        my $width = 1/$count;
       
        my @lineArray;
        my $textwidth = 160; # A4 paper width of 210mm less 2x 25mm margin
        my $columnMargins = 4;
        for(my $i = 0; $i<$count; $i++){
            push @lineArray, sprintf("m{%.1f"."mm}", ($width*$textwidth - $columnMargins));
        }

        if($lines[$i + 1] =~ /caption/){ # The next line is a caption
            my $offset = 1;
            my $captionLine = "";

            while ($captionLine !~ /\}/ && $offset < 100){
                $captionLine .= sprintf(" %s", $lines[$i + $offset]);
                $lines[$i + $offset] = "";
                $offset++;
            }
            $line = sprintf "\\begin{table}[hbt!]\n%s\\begin{tabular}{|%s|}\n", $captionLine, join("|",  @lineArray);
        } else {
            $line = sprintf("\\begin{table}[hbt!]\n\\begin{tabular}{|%s|}\n", join("|", @lineArray));
        }
    } elsif ($line =~ /\\tabularnewline/){
        $line =~ s/\\tabularnewline/\\tabularnewline \\hline/;
    } elsif ($line =~ /\\(top|mid|bottom)rule/){
        $lines[$i - 1] =~ s/\\hline//;
    } elsif ($line =~ /\\end\{longtable\}/){
        $line = "\\end{tabular}\n\\end{table}\n";
    } elsif ($line =~ /\\endfirsthead/){
        $lines[$i] = "";
        until($line =~ /\\endhead/){
            $lines[$i] = "";
            $i += 1;
            $line = $lines[$i]
        }
        $line = "";
    } elsif ($line =~ /endhead/){
        $line = "";
    } elsif ($line =~ /includegraphics(?:\[\S+\])?\{(\S+.svg)\}/){
        my $img_file = $1;

        my $converted_file_name = $img_file;
        $converted_file_name =~ s/.*\///;
        $converted_file_name =~ s/\.svg/.png/;
        $converted_file_name = sprintf "%s/%s", $directoryName, $converted_file_name;

        # convert the SVG to a PNG
        my $cmd = "/usr/bin/inkscape -z -d 300 -e \"$converted_file_name\" $img_file 2>&1";

        $result = `$cmd`;
        if($result =~ /:\s+ERROR/){
            printf "Error converting %s - %s", $img_file, $result;
        }

        $line =~ s/$img_file/$converted_file_name/;
    }

    $line =~ s/\\begin\{minipage\}\[\S+\]\{\S+\\columnwidth\}//;
    $line =~ s/\\end\{minipage\}//;

    $lines[$i] = $line;
}

# dump content array into a single string
my $content = join "", @lines;

# to allow an easier fix of double quotes
$content =~ s/\"(.*?)\"/``$1''/gs;


open( my $out, '>:encoding(utf8)', 'book.tex') or die("Unable to open file book.tex to wrwite revised version - $!");
print $out $content;
close $out;

# Generate the PDF from the modified tex 
$cmd = "PATH=/usr/bin/: pdflatex -halt-on-error -jobname temp book.tex";

for(my $i = 0; $i<3; $i++){
    # We regenerate the PDF three times so that the ToC works correctly.
    # the first run has an empty ToC, 
    # the second run has a ToC with incorrect page numbers
    # the third run is Just Right.

    $result = `$cmd`;
    if($result =~ /Fatal error occurred/){
        print $result;
    }
}

# Move the generated PDF to the output location

rename "temp.pdf", $outputFile;




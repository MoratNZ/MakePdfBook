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

my $filthyHack = 1;

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

# Do some cleanup on the HTML files, to remove a bunch of extraneous crap that mediawiki leaves in on export

foreach my $chapter (@chapters){
    open my $in, '<:encoding(utf8)', $chapter or die("Error: couldn't open $chapter for reading - $!");
    chomp (my @lines = <$in>);
    close $in;
    
    my @output;

    foreach my $line (@lines){
        $line =~s/&#8804;/SYMBOLlessThanOrEqualToSYMBOL/g;
        $line =~ s/<div class="thumbinner".*?(<img src=.*?\/>).*?<div class="thumbcaption"><div.*?<a.*?<\/a><\/div>(.*?)<\/div>/<figure>\1<figcaption>\2<\/figcaption><\/figure>/;
#<div class="thumbinner" style="width:302px;"><a href="/mediawiki/index.php/File:Fencing_Handbook_2020_Figure_1.png" class="image"><img src="/var/lib/mediawiki-1.34.0/images/thumb/3/3f/Fencing_Handbook_2020_Figure_1.png/300px-Fencing_Handbook_2020_Figure_1.png" decoding="async" width="300" height="291" class="thumbimage" srcset="/mediawiki/images/thumb/3/3f/Fencing_Handbook_2020_Figure_1.png/450px-Fencing_Handbook_2020_Figure_1.png 1.5x, /mediawiki/images/thumb/3/3f/Fencing_Handbook_2020_Figure_1.png/600px-Fencing_Handbook_2020_Figure_1.png 2x" /></a>  <div class="thumbcaption"><div class="magnify"><a href="/mediawiki/index.php/File:Fencing_Handbook_2020_Figure_1.png" class="internal" title="Enlarge"></a></div>Figure 1. With the handle vertical, the tip must touch the ground. In this example, the sword on the left is allowed, the sword on the right is not.</div></div><
        
        # Translation to preserve revision marking
        $line =~ s/<span class="revision">(.*?)<\/span>/HLSTART\1HLSTOP/ig;
        # <span class="revision">Discuss proposed changes with the Kingdom Armoured Combat Marshal and the Earl Marshal.</span>


        push @output, $line;
    }

    
    open my $out, '>', $chapter or die("Error: couldn't open $chapter for writing - $!");
    print $out @output;
    close $out;

}


# Build the tex file for the body of the book
my $sourceFileString = join(" ", @chapters);
my $templateFile = "template.tex";
$cmd = "PATH=/usr/bin/: pandoc  -f html -t latex --template $templateFile $titleOption -o book.original.tex $sourceFileString  2>&1";
$result = `$cmd`;

if($result){
    if( $result =~ /Cannot decode byte '(.*)'/){
        my $badByte = $1;
        print "Error producing initial tex file for book.\n".
        "There is a special character (utf character code [$badByte]) that pandoc has trouble with\n".
        "Unfortunately, you'll need to track it down and remove it.\n";
    } elsif ($result =~ /UTF-8 decoding error in (\S+) at byte offset (\S+)/){
        my $chapter_file = $1;
        my $character_position = int($2);
 

        my $context_amount = 40;

        open(FILE, "<", $chapter_file) or die("Error reading in $chapter_file trying to find bad byte at position $character_position - $!");
        my $file_content;
        {
            local $/;
            $file_content = <FILE>;
        }
        close FILE;

        my $title_guess = "";
        if($file_content =~ /\<h1\>(.*?)\<\/h1\>/){
            $title_guess = $1;
        }

        my $start_pos;
        my $end_pos = length $file_content;

        if($character_position > ($context_amount/2)){
            $start_pos = int($character_position - $context_amount/2);
        } else {
            $start_pos = 0;
        }

        my $context_snippet = substr($file_content, $start_pos, $context_amount);

        $context_snippet =~ s/\</&lt;/g;
        $context_snippet =~ s/\>/&gt;\n/g;

    
        printf "There is a problematic character at byte position %s in file %s %s . Here is %s characters of context starting from character %s \n%s\n%s\n%s",
            $character_position, 
            $chapter_file, 
            $title_guess ? "(My best guess is that this is the '$title_guess' chapter)" : "",
            $context_amount,
            $start_pos,
            "-" x 40,
            $context_snippet,
            "-" x 40,;
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
        # Regex matches:
        # \begin{longtable}[]{@{}l@{}}
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
        my $cmd = "/usr/bin/inkscape --export-dpi 300 --export-filename \"$converted_file_name\" $img_file 2>&1";

        #TODO add better handling of errors in this section

        $result = `$cmd`;
        if($result =~ /:\s+ERROR/){
            printf "Error converting %s - %s", $img_file, $result;
        }


        $line =~ s/$img_file/$converted_file_name/;
    }

    $line =~ s/\\begin\{minipage\}\[\S+\]\{\S+\\columnwidth\}//;
    $line =~ s/\\end\{minipage\}//;

    # And now employ a filthy hack to deal with a pandoc html>tex regression

    $line =~ s/^.section/\\chapter/;
    $line =~ s/^.subsection/\\section/;
    $line =~ s/^.subsubsection/\\subsection/;

    $lines[$i] = $line;
}

# dump content array into a single string
my $content = join "", @lines;

# to allow an easier fix of double quotes and other character issues
$content =~ s/\"(.*?)\"/``$1''/gs;

$content =~ s/SYMBOLlessThanOrEqualToSYMBOL/\$\\leq\$/gs;
$content =~ s/HLSTART(.*?)HLSTOP/\\textcolor{red}{\1}/gs;



open( my $out, '>:encoding(utf8)', 'book.tex') or die("Unable to open file book.tex to write revised version - $!");
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




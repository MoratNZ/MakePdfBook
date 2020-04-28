#! /usr/bin/env perl

use strict;
use File::Copy;

my $file = shift;
my $tmp_dir = "/tmp";

copy $file, $file.".original";

open(FILE, "<", $file) or die("Unable to open file $file - $!");

my @lines = <FILE>;
close FILE;

my $lineCount = scalar(@lines);

for(my $i = 0; $i < $lineCount; $i++){
    my $line = $lines[$i];
    if($line =~ /^\\begin\{longtable\}\[\]\{@\{\}(.*)@\{\}\}$/){
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
        $converted_file_name = sprintf "%s/%s", $tmp_dir, $converted_file_name;

        # convert the SVG to a PNG
        my $cmd = "/usr/bin/inkscape -z -e \"$converted_file_name\" $img_file";

        print $cmd;

        `$cmd`;

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



open(FILE, ">", $file) or die("Unable to open file $file to wrwite revised version - $!");

print FILE $content;

close FILE;

print "Tablefix completed successfully"
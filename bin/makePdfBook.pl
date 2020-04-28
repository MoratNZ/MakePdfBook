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

# Check whether the template file is present

# Check whether a titlepage is present

# Grab a list of all the chapters



printf "dir: %s\noutputFile:%s\n\n", $directoryName, $outputFile;
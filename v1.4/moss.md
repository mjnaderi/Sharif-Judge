# Cheat Detection

Sharif Judge uses **[Moss](http://theory.stanford.edu/~aiken/moss)** to detect similar codes. Moss (for a Measure Of Software Similarity) is an automatic system for determining the similarity of programs. To date, the main application of Moss has been in detecting plagiarism in programming classes.

You can send **Final** submitted codes (those selected by students as "Final Submission") to Moss server with one click.

Before using Moss, you must get a **Moss user id** and set your Moss user id in Sharif Judge. Read [this page](http://theory.stanford.edu/~aiken/moss) and register for Moss. You will receive a mail containing a perl script. Your user id is in that script.

This is a part of this perl script containing user id:

```perl

...

$server = 'moss.stanford.edu';
$port = '7690';
$noreq = "Request not sent.";
$usage = "usage: moss [-x] [-l language] [-d] [-b basefile1] ... [-b basefilen] [-m #] [-c \"string\"] file1 file2 file3 ...";

#
# The userid is used to authenticate your queries to the server; don't change it!
#
$userid=YOUR_MOSS_USER_ID;

#
# Process the command line options.  This is done in a non-standard
# way to allow multiple -b's.
#
$opt_l = "c";   # default language is c
$opt_m = 10;
$opt_d = 0;

...

```

Find your user id and use it in Sharif Judge for cheat detection. You don't need to put your user id in any file. Just save your user id in Sharif Judge's Moss page and Sharif Judge will use your user id in Moss perl script.

Your server must have `perl` installed to use Moss.

It is recommended to detect similar codes after assignment finishes. Because Sharif Judge just sends **Final** submissions to Moss and students can change their **Final** submissions before assignment finishes.

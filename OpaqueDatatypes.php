<?php
class CustomResult {
    public $name;            /* string */
    public $value;           /* string */
}
class ProcessReturn {
    public $CSS;             /* string */
    public $XHTML;           /* string */
    public $progressInfo;    /* string */
    public $questionEnd;     /* boolean */
    public $resources;       /* ArrayOfResource */
    public $results;         /* Results */
}
class Resource {
    public $content;         /* base64Binary */
    public $encoding;        /* string */
    public $filename;        /* string */
    public $mimeType;        /* string */
}
class Results {
    public $actionSummary;   /* string */
    public $answerLine;      /* string */
    public $attempts;        /* int */
    public $customResults;   /* ArrayOfCustomResult */
    public $questionLine;    /* string */
    public $scores;          /* ArrayOfScore */
}
class Score {
    public $axis;            /* string */
    public $marks;           /* int */
    public function __construct($axis, $mark) {
        $this->axis = $axis;
        $this->marks = $mark;
    }
}
class StartReturn {
    public $CSS;             /* string */
    public $XHTML;           /* string */
    public $progressInfo;    /* string */
    public $questionSession; /* string */
    public $resources;       /* ArrayOfResource */
    public $head;            /* string */
}
?>

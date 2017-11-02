<?php

class ConfFileEditor {

  private $filename;
  private $mustcommit = false;
  private $rows = 60;
  private $cols = 100;
  private $textName = 'confeditor';
  private $title = 'Edit a file';
  private $description = '<p>The file access rights or authorization file is stored in the root repos at svnroot/conf/authz. Here you can edit it using a html form</p>';

  function __construct( $filename ) {
    $this->filename = $filename;
    $this->save();
  }

  /**
   *  Output the file editor to the browser.
   */
  function _show() {
    global $PHP_SELF;
    echo "<fieldset><legend>Edit $this->filename.</legend>\n" .
    "$this->description\n" .
    "<form method='post' name='confeditform' action='" . $PHP_SELF . "'>\n" .
    "<textarea wrap='off' rows='$this->rows' cols='$this->cols' style='font-size:10pt;font-family:courier' name='$this->textName'>\n";
    if ( is_file( $this->filename ) ) {
      readfile( $this->filename );
    }
    echo "</textarea><br/>" .
    "<input type='reset'/>&nbsp;<input type='submit' name='save_confeditform' value='Save'/>\n" .
    "</form>\n" .
    "</fieldset>";
  }

  function show() {
    global $PHP_SELF;
    echo "<fieldset><legend>Edit $this->filename.</legend>\n"
    . "$this->description\n"
    . "<form method='post' name='confeditform' action='" . $PHP_SELF . "'>\n"
    . "<textarea name='$this->textName'>\n";
    if ( is_file( $this->filename ) ) {
      readfile( $this->filename );
    }
    echo "</textarea><div id='$this->textName' />" .
    "<input type='reset'/>&nbsp;<input type='submit' name='save_confeditform' value='Save'/>\n" .
    "</form>\n" .
    "</fieldset>";
  }

  /**
   * Save the POST fileeditorcontent.
   */
  function save() {
    if ( isSet( $_POST['save_confeditform'] ) && isSet( $_POST[$this->textName] ) ) {
      $fp = fopen( $this->filename, 'w+' );
      if ( $fp ) {
        if ( fwrite( $fp, $_POST[$this->textName] ) === FALSE ) {
          echo "cannot write $this->filename\n";
        } else {
          fclose( $fp );
          if ( $this->mustCommit ) {
            $cmdstring = "svn ci -m'wwwrun update of file' $this->filename";
            echo "<fieldset><legend>Auto commit result<legend>\n<pre>";
            @passthru( $cmdstring );
            echo "</pre></fieldset>";
          };
        }
      }
    }
  }

  /**
   * Set title.
   */
  function setTitle( $t ) {
    $this->title = $t;
  }

  /**
   * Set description.
   */
  function setDescription( $d ) {
    $this->description = $d;
  }

  /**
   * Set commit flag
   */
  function setMustCommit( $b ) {
    $this->mustCommit = $b;
  }

  function setRows( $r ) {
    $this->rows = $r;
    return $this;
  }

  function setCols( $c ) {
    $this->colss = $c;
    return $this;
  }

  function setTextName( $n ) {
    $this->textName = $n;
  }

}

?>
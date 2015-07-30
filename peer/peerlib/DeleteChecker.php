<?php
/**
 * @package prafda2
 * @author Pieter van den Hombergh
 */
/**
 * @abstract interface DeleteChecker, tests if deletes are allowed.
 * This kludge is necessary because table project_uren does not have alloacties as proper parent.
 * I used a java style interface definition, which although not completely supported by php 4,
 * serves as documentation for the programmer who now knows what to implement.
 * For the real world example look in @see allocaties.php
 * The DeleteChecker
 */
class DeleteChecker {
  /**
   * checks if delete is allowed
   * @param &$record, reference to assoc array: record to be deleted
   * @param &$dbMessage, messageBuffer to append to any db messages
   * @return boolean: true if delete is permitted.
   */
  function checkForDelete (&$record, &$dbMessage ) {
    return true;
  }
} /* DeleteChecker */
/* $Id: DeleteChecker.php 1723 2014-01-03 08:34:59Z hom $ */
?>

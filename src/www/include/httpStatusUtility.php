<?php

/* ===========================================================================
 * 
 * PREAMBLE
 * 
 * ======================================================================== */

/**
 * project LiMiT1
 * file include/httpStatusUtility.php
 * 
 * common definitions needed for all scripts dealing with http status values
 * 
 * @author Ulrich Kühn
 * @see https://github.com/ulkuehn/LiMiT1
 * @copyright (c) 2017, 2018, Ulrich Kühn
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
set_include_path ( pathinfo ( $_SERVER[ "DOCUMENT_ROOT" ] )[ "dirname" ] . "/www" );
require_once ("include/constants.php");
require_once ("include/configuration.php");
require_once ("include/utility.php");
require_once ("include/connectDB.php");
require_once ("include/globalNames.php");


/* ===========================================================================
 * 
 * FUNCTIONS
 * 
 * ======================================================================== */

/**
 * classifiy http status code using icons and bootstrap colors
 * 
 * @param int $status http status code
 * @return html code for classifying the status
 */
function httpStatusBadge ( $status )
{
  switch ( substr ( $status,
                    0,
                    1 ) )
  {
    case "1":
      return "<div class=\"label label-info\"><i class=\"fa fa-info fa-lg\"></i></div>";
    case "2":
      return "<div class=\"label label-success\"><i class=\"fa fa-check fa-lg\"></i></div>";
    case "3":
      return "<div class=\"label label-warning\"><i class=\"fa fa-share fa-lg\"></i></div>";
    case "4":
    case "5":
      return "<div class=\"label label-danger\"><i class=\"fa fa-close fa-lg\"></i></div>";
    default:
      return "<div class=\"label label-primary\"><i class=\"fa fa-question fa-lg\"></i></div>";
  }
}


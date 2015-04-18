<?php
class esql{
  //EasySQL Mini-API
  public static function dbc($link){ //connect
    mysql_connect(config::$dbhost,config::$dbuser,config::$dbpass) or die (style::error(lang::$sqlerror."(".$link.")"));
    mysql_select_db(config::$dbname) or die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'."cannot connect".'('.$link.')</div></div>');
  }

  public static function dbq($link,$action, $col/*leave empty for delete, set param for update,cols für insert*/,$table,$filter=""/*filter -> values bei insert*/,$debug=false){
    $q="";
    $table=config::$prefix.$table;
    if($action=="select"){
      if($filter)
        $q="select ".$col." from ".$table." where ".$filter;
      else
        $q="select ".$col." from ".$table;
    }
    if($action=="update"&&$filter)
      $q="update ".$table." set ".$col." where ".$filter;
    if($action=="delete"&&$filter)
      $q="delete from ".$table." where ".$filter;
    if($action=="insert")
      $q='insert into '.$table.' '.'('.$col.') values ('.$filter.')';
    if($debug) 
      echo $q."<br>"; // -> debug
    if($q) {
      $q=mysql_query($q);
      if($q===false) {
        if($debug)
          echo mysql_error();
        die ("cannot ".$action." in db (".$link.")");
      }
    }
    return $q;
  }
  
  public static function dbres($res,$row=0,$col=0) {
    return mysql_result($res,$row,$col);
  }
  
  public static function dbclose($link="") {
    mysql_close();
  }
}
?>

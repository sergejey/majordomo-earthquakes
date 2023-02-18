<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['eq_events_qry'];
  } else {
   $session->data['eq_events_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_eq_events="eq_events.ADDED DESC";
  $out['SORTBY']=$sortby_eq_events;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT eq_events.*, eq_places.TITLE as PLACE FROM eq_events LEFT JOIN eq_places ON eq_events.PLACE_ID=eq_places.ID WHERE $qry ORDER BY ".$sortby_eq_events);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required

   }
   $out['RESULT']=$res;
  }

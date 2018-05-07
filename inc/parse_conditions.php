<?php
function parse_condition($condition){
   
    $operators = [ '>' , '<' , '!=' , '!==' , '==' , '===' , '<=' , '>=' ];
    $operator = '';
    foreach($operators as $op){
      if ( $operator ) break;
      list ($a,$b) = explode($op,$condition);
      $a = str_replace(['$','()'],'',trim($a));
      $b = str_replace(['$','()'],'',trim($b));
      if ( $a && $b ) {
        $operator=$op;
      }
    }
    
    $values = prepare_values([$a,$b]);

    $cmp1 = isset($values[$a])?($values[$a]):($a);

    $cmp2 = isset($values[$b])?($values[$b]):($b);
  
    $cmp1 = $cmp1=='true' ? true : $cmp1;
    $cmp2 = $cmp2=='true' ? true : $cmp2;
  
  //echo $cmp1.$operator.$cmp2;
    switch($operator){
        case '==':
            return($cmp1 == $cmp2);
            break;
        case '!=':
            return($cmp1 != $cmp2);
            break;
        case '===':
            return($cmp1 === $cmp2);
            break;
        case '!==':
            return($cmp1 !== $cmp2);
            break;
         case '<=':
            return($cmp1 <= $cmp2);
            break;
         case '>=':
            return($cmp1 >= $cmp2);
            break;
         case '>':
            return($cmp1 > $cmp2);
            break;
         case '<':
            return($cmp1 < $cmp2);
            break;
      default:
            return true;
            break;
    }   
}


/*-----------------------------------------------------------------------------------*/
/* Value preps
/*-----------------------------------------------------------------------------------*/
function prepare_values($keys,$post=NULL){
	global $wp_query;
  if( !isset($wp_query)) return;
	if ($post!=NULL) global $post;
	if ($post->ID) $var=$post; 
		else $var=$wp_query->get_queried_object();
/**  Set custom vars **/
	$id=$var->ID;
	if($id) $permalink=get_permalink($id);
	$name=get_queried_object()->name;
	if ( isset($var->term_id) ) $description=do_shortcode(wpautop($var->description)); else {
		if ( isset($var->description) ) $var->description=NULL; /// to work only with terms descriptions
	} 

	if(!is_single() && !$content) $content=true; // if it is an elementor format it would not work... please research a little bit | nu merge sa se cheme elementor de id cand este in ea...
	$post_excerpt = $post_excerpt ? true : false;

	// add your own custom vars

	$custom_vars=apply_filters( 'eleconditions_vars', $custom_vars ); 
  
  	$value==array();
  
	if ( isset($custom_vars) ) foreach($custom_vars as $ck=>$cv){
		$$ck = $cv;
	}

/** end seting custom vars **/
// adding the values in keys

	if ( isset($keys)) {
		foreach ($keys as $key) {
			$value[$key]=isset($$key) ? $$key : $var->$key; //echo "<br/> ".$key." "; print_r($var->$key);
			if ($value[$key]=="") { //echo "<br/> ".$key." "; print_r($custom_field);
				//Daca nu a gasit nici o proprietate a obeictului cauta custom field
				if ($post->ID) {
					$custom_field=get_post_meta( $post->ID, $key, true); //echo "<br/>..".$key." :"; print_r($custom_field);
				}
				$value[$key]=$custom_field ? $custom_field : "";//pune custom field sau sa stearga keya daca nu are valoare 
				if ($value[$key]=="" && function_exists("getProductAttributes") ) $value[$key] = getProductAttributes($post->ID,$key); // iau custom product attribute
				if ($value[$key]=="" && function_exists('get_field') && $var->term_id) $value[$key] = get_field($key, $var->taxonomy.'_'.$var->term_id);// iau custom field de la taxonomie
				if ($value[$key]=="") $value[$key]=$wp_query->query_vars[$key]; //get query_vars
			}
		}
	}
  //print_r($value);
	return $value;
}
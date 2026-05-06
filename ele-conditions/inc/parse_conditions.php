<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function elecond_get_meta_acf_options(): array {
	$options = [ '' => __( '— select field —', 'ele-conditions' ) ];

	// ACF field groups
	if ( function_exists( 'acf_get_field_groups' ) ) {
		foreach ( acf_get_field_groups() as $group ) {
			$fields = acf_get_fields( $group['key'] );
			if ( ! $fields ) continue;
			foreach ( $fields as $field ) {
				$options[ $field['name'] ] = $field['label'] . '  [ACF]';
			}
		}
	}

	// Post meta keys (exclude internal WP keys starting with _)
	global $wpdb;
	$keys = $wpdb->get_col(
		"SELECT DISTINCT meta_key FROM {$wpdb->postmeta}
		 WHERE meta_key NOT LIKE '\_%'
		 ORDER BY meta_key LIMIT 300"
	);
	foreach ( $keys as $key ) {
		if ( ! isset( $options[ $key ] ) ) {
			$options[ $key ] = $key . '  [meta]';
		}
	}

	$options['__manual__'] = __( '— type manually —', 'ele-conditions' );

	return $options;
}

function elecond_evaluate_group( array $conditions, bool $debug = false ): bool {
	if ( empty( $conditions ) ) return true;

	$result     = null;
	$prev_logic = 'AND';

	foreach ( $conditions as $cond ) {
		$type = $cond['cond_type'] ?? 'simple';

		if ( $type === 'time_interval' ) {
			$cond_result = elecond_check_time_interval(
				$cond['cond_time_from'] ?? '',
				$cond['cond_time_to']   ?? ''
			);
		} elseif ( $type === 'date_interval' ) {
			$cond_result = elecond_check_date_interval(
				$cond['cond_datetime_from'] ?? '',
				$cond['cond_datetime_to']   ?? ''
			);
		} else {
			$preset = $cond['cond_var_preset'] ?? '';
			if ( $preset === 'acf_meta' ) {
				$picked = $cond['cond_var_acf_meta'] ?? '';
				$var    = $picked === '__manual__'
					? ( $cond['cond_var_acf_manual'] ?? '' )
					: $picked;
			} elseif ( $preset === 'user_meta' ) {
				$meta_key = $cond['cond_var_user_meta'] ?? '';
				$var      = $meta_key !== '' ? 'um_' . $meta_key : '';
			} elseif ( $preset === 'custom' ) {
				$var = $cond['cond_var_custom'] ?? '';
			} else {
				$var = $preset;
			}

			if ( $var === '' ) {
				$prev_logic = $cond['cond_logic'] ?? 'AND';
				continue;
			}

			$expr        = $var . ( $cond['cond_operator'] ?? '==' ) . ( $cond['cond_value'] ?? '' );
			$cond_result = elecond_parse_condition( $expr, $debug );
		}

		if ( $result === null ) {
			$result = $cond_result;
		} elseif ( $prev_logic === 'AND' ) {
			$result = $result && $cond_result;
		} else {
			$result = $result || $cond_result;
		}

		$prev_logic = $cond['cond_logic'] ?? 'AND';
	}

	return $result ?? true;
}

function elecond_check_date_interval( string $from, string $to ): bool {
	if ( $from === '' && $to === '' ) return true;

	$tz  = wp_timezone();
	$now = new DateTime( 'now', $tz );

	// Normalize: datetime-local uses T separator (2026-01-15T09:00), replace with space
	$from = str_replace( 'T', ' ', $from );
	$to   = str_replace( 'T', ' ', $to );

	if ( $from !== '' ) {
		$from_dt = DateTime::createFromFormat( 'Y-m-d H:i', $from, $tz )
			?: DateTime::createFromFormat( 'Y-m-d', $from, $tz );
		if ( $from_dt && $now < $from_dt ) return false;
	}

	if ( $to !== '' ) {
		$to_dt = DateTime::createFromFormat( 'Y-m-d H:i', $to, $tz );
		if ( ! $to_dt ) {
			$to_dt = DateTime::createFromFormat( 'Y-m-d', $to, $tz );
			if ( $to_dt ) $to_dt->setTime( 23, 59, 59 );
		}
		if ( $to_dt && $now > $to_dt ) return false;
	}

	return true;
}

function elecond_check_time_interval( string $from, string $to ): bool {
	if ( $from === '' || $to === '' ) return true;

	$tz      = wp_timezone();
	$now     = new DateTime( 'now', $tz );
	$current = (int) $now->format( 'Hi' ); // e.g. 1430 for 14:30

	$from_int = (int) str_replace( ':', '', $from ); // e.g. 900 for 09:00
	$to_int   = (int) str_replace( ':', '', $to );

	// Normal range (e.g. 09:00–17:00) or cross-midnight (e.g. 22:00–06:00)
	if ( $from_int <= $to_int ) {
		return $current >= $from_int && $current <= $to_int;
	}
	return $current >= $from_int || $current <= $to_int;
}

//// nu merge cu variabile cu valori booleene!!!!!!!! repara!!!! vezi exemplu my var
function elecond_check_value($val,$values){
  switch($val){
    case 'true':
      return true;
      break;
    case 'false':
      return false;
      break;
    case 'null':
      return NULL;
      break;
  }
  if (isset( $values[$val] )) 
    return $values[$val] != "" ? $values[$val] : $val; 
  return  $val ;
}
function elecond_evaluate_values(&$cmp1,&$cmp2,$a,$b){
  $special=['true','false','null'];
  $changed1= (string)$cmp1 == $a ? false : true;
  $changed2= (string)$cmp2 == $b ? false : true;
  
  if ( in_array($a,$special) && !$changed2 ) $cmp2="";
  if ( in_array($b,$special) && !$changed1 ) $cmp1="";
  
  //check for numbers
  if ( is_numeric($a) && !$changed2 ) $cmp2=0;
  if ( is_numeric($b) && !$changed1 ) $cmp1=0;
  
}
function elecond_parse_condition($condition,$debug=false){
   
    $operators = [ '>' , '<' , '!=' , '!==' , '==' , '===' , '<=' , '>=' ];
    $clean = ['$','()','{','}'];
    $operator = '';
    foreach($operators as $op){
      if ( $operator ) break;
      $vars = explode($op,$condition);
      $a = isset($vars[0]) ? $vars[0] : "";
      $b = isset($vars[1]) ? $vars[1] : "";
      $a = str_replace( $clean , '' , trim($a) );
      $b = str_replace( $clean , '' , trim($b) );
      if ( $a && $b ) {
        $operator=$op;
      }
    }
    
    $values = elecond_prepare_values([$a,$b]);

    $cmp1 = elecond_check_value($a,$values);
  
    $cmp2 = elecond_check_value($b,$values);
  
    elecond_evaluate_values($cmp1,$cmp2,$a,$b);
  
  
  //echo $cmp1.$operator.$cmp2;
    switch($operator){
        case '=':
            $result = ($cmp1 == $cmp2) ? true : false;
            break;
        case '==':
            $result = ($cmp1 == $cmp2) ? true : false;
            break;
        case '!=':
            $result = ($cmp1 != $cmp2) ? true : false;
            break;
        case '===':
            $result = ($cmp1 === $cmp2) ? true : false;
            break;
        case '!==':
            $result = ($cmp1 !== $cmp2) ? true : false;
            break;
         case '<=':
            $result = ($cmp1 <= $cmp2) ? true : false;
            break;
         case '>=':
            $result = ($cmp1 >= $cmp2) ? true : false;
            break;
         case '>':
            $result = ($cmp1 > $cmp2) ? true : false;
            break;
         case '<':
            $result = ($cmp1 < $cmp2) ? true : false;
            break;
      default:
            $result = true;
            break;
    }
    if ($debug && ( current_user_can('editor') || current_user_can('administrator') ))
      elecond_debug($condition, $a, $b, $cmp1,$cmp2,$operator,$result);
  return $result;
}


/*-----------------------------------------------------------------------------------*/
/* Value preps
/*-----------------------------------------------------------------------------------*/

function elecond_getQueryVar($keys){
  global $wp_query, $post;
  if( !isset($wp_query)) return NULL;
  if (isset($post->ID)) $var=$post;//if it post we get the post values
    else $var=$wp_query->get_queried_object();//if not will get other values
  return $var;  
}

function elecond_set($value=NULL){
  return isset($value) ? $value : "";
}


function elecond_prepare_values($keys){
  $var=elecond_getQueryVar($keys);
/**  Set custom vars **/
  $id=elecond_set($var->ID);
  if(isset($id)) $permalink=get_permalink($id);
  $name=elecond_set(get_queried_object()->name);
  if ( isset($var->term_id) ) $description=do_shortcode(wpautop($var->description)); else {
    if ( isset($var->description) ) $var->description=NULL; /// to work only with terms descriptions
  } 

  if(!is_single() && !isset($content)) $content=true; // if it is an elementor format it would not work... please research a little bit | nu merge sa se cheme elementor de id cand este in ea...
  $post_excerpt = isset($post_excerpt) ? true : false;

  // add your own custom vars
  $custom_vars=[];
  
  $custom_vars=apply_filters( 'eleconditions_vars', $custom_vars ); 
  
  $value=array();
  
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
        if (isset($post->ID)) {
          $custom_field=get_post_meta( $post->ID, $key, true); //echo "<br/>..".$key." :"; print_r($custom_field);
        }
        $value[$key]=isset($custom_field) ? $custom_field : "";//pune custom field sau sa stearga keya daca nu are valoare
        if ($value[$key]=="" && function_exists("getProductAttributes") ) $value[$key] = getProductAttributes($post->ID,$key); // iau custom product attribute
        // ACF: post field
        if ($value[$key]=="" && function_exists('get_field') && isset($post->ID)) {
          $acf_val = get_field($key, $post->ID);
          if ($acf_val !== false && $acf_val !== null && $acf_val !== '') $value[$key] = $acf_val;
        }
        // ACF: taxonomy field
        if ($value[$key]=="" && function_exists('get_field') && isset($var->term_id)) $value[$key] = get_field($key, $var->taxonomy.'_'.$var->term_id);
        if ($value[$key]=="")
            $value[$key]=isset($wp_query->query_vars[$key]) ? $wp_query->query_vars[$key] : ""; //get query_vars
        // User meta: um_ prefix (e.g. um_city → get_user_meta on current user with key 'city')
        if ( $value[$key] === '' && strncmp( $key, 'um_', 3 ) === 0 ) {
          $u = wp_get_current_user();
          if ( $u->ID ) {
            $um_val = get_user_meta( $u->ID, substr( $key, 3 ), true );
            if ( $um_val !== false && $um_val !== '' ) $value[$key] = $um_val;
          }
        }
      }
    }
  }
  //print_r($value);
  return $value;
}

function elecond_debug($condition,$var1,$var2,$val1,$val2,$operator,$result){
  $color1 = $var1 == $val1 ? "lightpink" : "lightgreen";
  $color2 = $var2 == $val2 ? "lightpink" : "lightgreen";
  $color3 = "lightblue";
  $color4 = "orange";
  $color5 = "black";
  $result = $result ? "true" : "false";
?>
<style>
  .ele_cond_debug span{
    padding:2px 5px;
  }
</style>
<div style="font-family:monospace; line-height: 2em;" class="ele_cond_debug">
 
  <div style="color:lightgray;">
   <span style="background:<?php echo esc_attr( $color5 );?>;"><?php echo esc_html( $condition ); ?></span>
  </div>
  <div>
    <span style="background:<?php echo esc_attr( $color1 );?>;"><?php echo esc_html( $val1 );?></span>
    <span style="background:<?php echo esc_attr( $color3 );?>;"><?php echo esc_html( $operator );?></span>
    <span style="background:<?php echo esc_attr( $color2 );?>;"><?php echo esc_html( $val2 );?></span>
    <span style="background:<?php echo esc_attr( $color5 );?>; color:white; font-weight:bold;">-&gt;</span>
    <span style="background:<?php echo esc_attr( $color4 );?>;"><?php echo esc_html( $result );?></span>
  </div>
<?php
}
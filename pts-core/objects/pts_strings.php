<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2012, Phoronix Media
	Copyright (C) 2010 - 2012, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// TODO XXX: some of these functions may be better optimized using the ctype functions: http://us.php.net/manual/en/ref.ctype.php

class pts_strings
{
	const CHAR_LETTER = 2;
	const CHAR_NUMERIC = 4;
	const CHAR_DECIMAL = 8;
	const CHAR_SPACE = 16;
	const CHAR_DASH = 32;
	const CHAR_UNDERSCORE = 64;
	const CHAR_COLON = 128;
	const CHAR_COMMA = 256;
	const CHAR_SLASH = 512;
	const CHAR_AT = 1024;
	const CHAR_PLUS = 2048;
	const CHAR_SEMICOLON = 4096;
	const CHAR_EQUAL = 8192;

	public static function is_url($string)
	{
		$components = parse_url($string);
		return $components != false && isset($components['scheme']) && isset($components['host']);
	}
	public static function is_version($string)
	{
		// Only numeric or decimal, and at least a decimal (not int)
		return pts_strings::string_only_contains($string, (pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL)) && pts_strings::string_contains($string, (pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL));
	}
	public static function trim_search_query($value)
	{
		$search_break_characters = array('@', '(', '/', '+', '[', '<', '/');
		for($i = 0, $x = strlen($value); $i < $x; $i++)
		{
			if(in_array($value[$i], $search_break_characters))
			{
				$value = substr($value, 0, $i);
				break;
			}
		}

		if((is_numeric($value) && substr($value, 0, 2) != '0x') || $value == null)
		{
			return;
		}

		// Remove multiplier if prepended to string
		$multiplier = strpos($value, ' x ');
		if($multiplier !== false && is_numeric(substr($value, 0, $multiplier)))
		{
			$value = substr($value, ($multiplier + 3));
		}

		$value = str_replace('& ', null, $value);

		if(substr($value, -1) == '.')
		{
			$value = substr($value, 0, -1);
		}

		if(($w = stripos($value, 'WARNING')) !== false)
		{
			// to get rid of Scheisse like 'Gtk-WARNING **: Unable'
			$value = substr($value, 0, strrpos($value, ' ', (0 - (strlen($value) - $w))));
		}

		// Remove other beginning or ending words based upon conditions
		$words = explode(' ', trim($value));
		$c = count($words);
		switch($c)
		{
			case 1:
				if(isset($words[0][2]) && in_array(substr($words[0], -2), array('MB', 'GB', '0W')))
				{
					// Just searching a disk / memory size or a power supply wattage
					$words = array();
				}
				break;
			default:
				$last_index = ($c - 1);
				if(strpos($words[$last_index], 'v1') !== false || strpos($words[$last_index], 'MB') !== false || strpos($words[$last_index], 'GB') !== false)
				{
					// Version number being appended to product (some mobos) or the MB/GB size for GPUs
					array_pop($words);
				}
				else if(strpos($words[0], 'GB') !== false)
				{
					// Likely disk size in front of string
					array_shift($words);
				}
				break;
		}

		return implode(' ', $words);
	}
	public static function string_bool($string)
	{
		// Used for evaluating if the user inputted a string that evaluates to true
		return in_array(strtolower($string), array('true', '1'));
	}
	public static function add_trailing_slash($path)
	{
		if(PTS_IS_CLIENT && phodevi::is_windows() && strpos($path, ':\\') === 1)
		{
			return $path . (substr($path, -1) == '\\' ? null : '\\');
		}
		else
		{
			return $path . (substr($path, -1) == '/' ? null : '/');
		}
	}
	public static function trim_explode($delimiter, $to_explode)
	{
		return empty($to_explode) ? array() : array_map('trim', explode($delimiter, $to_explode));
	}
	public static function comma_explode($to_explode)
	{
		return self::trim_explode(',', $to_explode);
	}
	public static function colon_explode($to_explode)
	{
		return self::trim_explode(':', $to_explode);
	}
	public static function first_in_string($string, $delimited_by = ' ')
	{
		return ($t = strpos($string, $delimited_by)) ? substr($string, 0, $t) : $string;
	}
	public static function last_in_string($string, $delimited_by = ' ')
	{
		return ($t = strrpos($string, $delimited_by)) ? substr($string, ($t + 1)) : $string;
	}
	public static function array_list_to_string($array, $bold_items = false, $append_to_end = null)
	{
		$count = count($array);

		if($bold_items)
		{
			foreach($array as &$item)
			{
				$item = '<strong>' . $item . '</strong>';
			}
		}

		if($count > 1)
		{
			$temp = array_pop($array);
			array_push($array, 'and ' . $temp);
		}

		return implode(($count > 2 ? ', ' : ' ') . ' ', $array) . ($append_to_end != null ? ' ' .  $append_to_end . ($count > 1 ? 's' : null) : null);
	}
	public static function has_in_string($string, $r)
	{
		$has_in_string = false;

		foreach($r as $string_to_check)
		{
			if(strpos($string, $string_to_check) !== false)
			{
				$has_in_string = $string_to_check;
				break;
			}
		}

		return $has_in_string;
	}
	public static function has_in_istring($string, $r)
	{
		$has_in_string = false;

		foreach($r as $string_to_check)
		{
			if(stripos($string, $string_to_check) !== false)
			{
				$has_in_string = $string_to_check;
				break;
			}
		}

		return $has_in_string;
	}
	public static function random_characters($length)
	{
		$random = null;

		for($i = 0; $i < $length; $i++)
		{
			$random .= chr(rand(65, 90));
		}

		return $random;
	}
	public static function find_longest_string(&$string_r)
	{
		if(!is_array($string_r))
		{
			return $string_r;
		}

		$longest_string = null;
		$longest_string_length = 0;

		foreach($string_r as $one_string)
		{
			if(is_array($one_string))
			{
				$one_string = self::find_longest_string($one_string);
			}

			$one_string = strval($one_string);
			if(isset($one_string[$longest_string_length]))
			{
				$longest_string = $one_string;
				$longest_string_length = strlen($one_string);
			}
		}

		return $longest_string;
	}
	public static function char_is_of_type($char, $attributes)
	{
		$i = ord($char);

		if(($attributes & self::CHAR_LETTER) && (($i > 64 && $i < 91) || ($i > 96 && $i < 123)))
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_NUMERIC) && $i > 47 && $i < 58)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_DECIMAL) && $i == 46)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_DASH) && $i == 45)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_UNDERSCORE) && $i == 95)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_COLON) && $i == 58)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_SPACE) && $i == 32)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_COMMA) && $i == 44)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_AT) && $i == 64)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_PLUS) && $i == 43)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_SEMICOLON) && $i == 59)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_EQUAL) && $i == 61)
		{
			$is_of_type = true;
		}
		else if(($attributes & self::CHAR_SLASH) && ($i == 47 || $i == 92))
		{
			$is_of_type = true;
		}
		else
		{
			$is_of_type = false;
		}

		return $is_of_type;
	}
	public static function trim_spaces($str)
	{
		// get rid of multiple/redundant spaces that are next to each other
		$new_str = null;
		for($i = strlen($str); $i > 0; $i--)
		{
			// 32 is a ASCII space
			if(ord($str[($i - 1)]) != 32 || ($i < 2 || ord($str[($i - 2)]) != 32))
			{
				$new_str = $str[$i - 1] . $new_str;
			}
		}

		return trim($new_str);
	}
	public static function remove_redundant($string, $redundant_char)
	{
		$prev_char = $string[0];

		for($i = 1, $l = strlen($string); $i < $l; $i++)
		{
			$this_char = $string[$i];

			if($this_char == $redundant_char && $prev_char == $redundant_char)
			{
				$string[($i - 1)] = null;
			}

			$prev_char = $this_char;
		}

		return trim($string);
	}
	public static function strip_string($str)
	{
		// Clean a string containing hardware information of some common things to change/strip out
		$change_phrases = array(
			'MCH' => 'Memory Controller Hub',
			'AMD' => 'Advanced Micro Devices',
			'MSI' => 'MICRO-STAR INTERNATIONAL',
			'SiS' => 'Silicon Integrated Systems',
			'Abit' => 'http://www.abit.com.tw/',
			'ASUS' => 'ASUSTeK',
			'HP' => 'Hewlett-Packard',
			'NVIDIA' => 'nVidia',
			'HDD' => 'HARDDISK',
			'Intel' => 'Intel64',
			'HD' => 'High Definition',
			'IGP' => array('Integrated Graphics Controller', 'Express Graphics Controller', 'Integrated Graphics Device', 'Chipset Integrated')
			);

		foreach($change_phrases as $new_phrase => $original_phrase)
		{
			$str = str_ireplace($original_phrase, $new_phrase, $str);
		}

		$remove_phrases = array('incorporation', 'corporation', 'corp.', 'invalid', 'technologies', 'technology', 'version', 'computer', 'To Be Filled By', 'ODM', 'O.E.M.', 'Desktop Reference Platform', 'small form factor', 'convertible', 'group', 'chipset', 'community', 'reference', 'communications', 'semiconductor', 'processor', 'host bridge', 'adapter', 'CPU', 'platform', 'international', 'express', 'graphics', 'none', 'electronics', 'integrated', 'alternate', 'quad-core', 'memory', 'series', 'network', 'motherboard', 'serverengines', 'Manufacturer', 'x86/mmx/sse2', '/AGP/SSE/3DNOW!', '/AGP/SSE2', 'controller', '(extreme graphics innovation)', 'pci-e_gfx and ht3 k8 part', 'pci-e_gfx and ht1 k8 part', 'Northbridge only', 'dual slot', 'dual-core', 'dual core', 'microsystems', 'not specified', 'single slot', 'genuine', 'unknown device', 'systemberatung', 'gmbh', 'graphics adapter', 'video device', 'http://', 'www.', '.com', '.tw/', '/pci/sse2/3dnow!', '/pcie/sse2', '/pci/sse2', 'balloon', 'network connection', 'ethernet', 'limited.', ' system', 'compliant', 'co. ltd', 'co.', 'ltd.', 'LTD ', '�', '(r)', '(tm)', 'inc.', 'inc', '6.00 PG', ',', '\'', '_ ', '_ ', 'corp', 'product name', 'base board', 'pci to pci', ' bridge', ' release ', 'with Radeon HD');
		$str = str_ireplace($remove_phrases, ' ', $str);

		if(($w = stripos($str, 'WARNING')) !== false)
		{
			// to get rid of Scheisse like 'Gtk-WARNING **: Unable'
			$str = substr($str, 0, strrpos($str, ' ', (0 - (strlen($str) - $w))));
		}

		$str = pts_strings::trim_spaces($str);

		// Fixes an AMD string issue like 'FX -4100' due to stripping (TM) from in between characters, possibly other cases too
		$str = str_replace(' -', '-', $str);

		return $str;
	}
	public static function remove_lines_containing($contents, $contains)
	{
		foreach($contains as $needle)
		{
			while(($x = stripos($contents, $needle)) !== false)
			{
				$affected_line_begins = strrpos($contents, PHP_EOL, (0 - strlen($contents) + $x));
				$affected_line_ends = strpos($contents, PHP_EOL, $x);
				$contents = substr($contents, 0, $affected_line_begins) . ($affected_line_ends === false ? null : substr($contents, $affected_line_ends));
			}
		}

		return $contents;
	}
	public static function pts_version_to_codename($version)
	{
		$version = substr($version, 0, 3);

		$codenames = array(
			'1.0' => 'Trondheim',
			'1.2' => 'Malvik',
			'1.4' => 'Orkdal',
			'1.6' => 'Tydal',
			'1.8' => 'Selbu',
			'2.0' => 'Sandtorg',
			'2.2' => 'Bardu',
			'2.4' => 'Lenvik',
			'2.6' => 'Lyngen',
			'2.8' => 'Torsken',
			'2.9' => 'Iveland', // early PTS3 development work
			'3.0' => 'Iveland',
			'3.2' => 'Grimstad',
			'3.4' => 'Lillesand',
			'3.6' => 'Arendal',
			'3.8' => 'Bygland'
			);

		return isset($codenames[$version]) ? $codenames[$version] : null;
	}
	public static function parse_week_string($week_string, $delimiter = ' ')
	{
		$return_array = array();

		foreach(array('S', 'M', 'T', 'W', 'TH', 'F', 'S') as $day_int => $day_char)
		{
			if($week_string[$day_int] == 1)
			{
				array_push($return_array, $day_char);
			}
		}

		return implode($delimiter, $return_array);
	}
	public static function remove_from_string($string, $attributes)
	{
		$string_r = str_split($string);
		$new_string = null;

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == false)
			{
				$new_string .= $char;
			}
		}

		return $new_string;
	}
	public static function keep_in_string($string, $attributes)
	{
		$string_r = str_split($string);
		$new_string = null;

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == true)
			{
				$new_string .= $char;
			}
		}

		return $new_string;
	}
	public static function string_only_contains($string, $attributes)
	{
		$string_r = str_split($string);

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == false)
			{
				return false;
			}
		}

		return true;
	}
	public static function string_contains($string, $attributes)
	{
		$string_r = str_split($string);

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == true)
			{
				return true;
			}
		}

		return false;
	}
	public static function times_occurred($string, $attributes)
	{
		$string_r = str_split($string);
		$times_matched = 0;

		foreach($string_r as $char)
		{
			if(pts_strings::char_is_of_type($char, $attributes) == true)
			{
				$times_matched++;
			}
		}

		return $times_matched;
	}
	public static function proximity_match($search, $match_to)
	{
		// Proximity search in $search string for * against $match_to
		$search = explode('*', $search);
		$is_match = true;

		if(count($search) == 1)
		{
			$is_match = false;
		}

		for($i = 0; $i < count($search) && $is_match && !empty($search[$i]); $i++)
		{
			if(($match_point = strpos($match_to, $search[$i])) !== false && ($i > 0 || $match_point == 0))
			{
				$match_to = substr($match_to, ($match_point + strlen($search[$i])));
			}
			else
			{
				$is_match = false;
			}
		}

		return $is_match;
	}
	public static function result_quantifier_to_string($result_quantifier)
	{
		switch($result_quantifier)
		{
			case 'MAX':
				$return_str = 'Maximum';
				break;
			case 'MIN':
				$return_str = 'Minimum';
				break;
			case 'NULL':
				$return_str = null;
				break;
			case 'AVG':
			default:
				$return_str = 'Average';
				break;
		}

		return $return_str;
	}
	public static function format_time($time, $input_format = 'SECONDS', $standard_version = true, $round_to = 0)
	{
		switch($input_format)
		{
			case 'MINUTES':
				$time_in_seconds = $time * 60;
				break;
			case 'SECONDS':
			default:
				$time_in_seconds = $time;
				break;
		}

		if($round_to > 0)
		{
			$time_in_seconds += $round_to - ($time_in_seconds % $round_to);
		}

		$formatted_time = array();

		if($time_in_seconds > 0)
		{
			$time_r = array();
			$time_r[0] = array(floor($time_in_seconds / 3600), 'Hour');
			$time_r[1] = array(floor(($time_in_seconds % 3600) / 60), 'Minute');
			$time_r[2] = array($time_in_seconds % 60, 'Second');

			foreach($time_r as $time_segment)
			{
				if($time_segment[0] > 0)
				{
					$formatted_part = $time_segment[0];

					if($standard_version)
					{
						$formatted_part .= ' ' . $time_segment[1];

						if($time_segment[0] > 1)
						{
							$formatted_part .= 's';
						}
					}
					else
					{
						$formatted_part .= strtolower(substr($time_segment[1], 0, 1));
					}

					array_push($formatted_time, $formatted_part);
				}
			}
		}

		return implode(($standard_version ? ', ' : null), $formatted_time);
	}
	public static function days_ago_format_string($days_ago)
	{
		if($days_ago < 30)
		{
			$days_ago .= ' day' . ($days_ago > 1 ? 's': null);
		}
		else
		{
			$days_ago = floor($days_ago / 30);

			if($days_ago >= 12)
			{
				$year = floor($days_ago / 12);
				$months = $days_ago % 12;

				$days_ago = $year . ' year' . ($year > 1 ? 's': null);

				if($months > 0)
				{
					$days_ago .= ', ' . $months . ' month' . ($months > 1 ? 's': null);
				}
			}
			else
			{
				$days_ago = $days_ago . ' month' . ($days_ago > 1 ? 's': null);
			}
		}

		return $days_ago;
	}
	public static function time_stamp_to_string($time_stamp, $string_type)
	{
		$stamp_half = explode(' ', trim($time_stamp));
		$date = explode('-', $stamp_half[0]);
		$time = explode(':', (isset($stamp_half[1]) ? $stamp_half[1] : '00:00:00'));

		return date($string_type, mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]));
	}
	public static function system_category_to_openbenchmark_category($category)
	{
		switch($category)
		{
			case 'Graphics':
				$category = 'GPU';
				break;
			case 'Processor':
			case 'System':
				$category = 'CPU';
				break;
			case 'File-System':
				$category = 'File System';
				break;
		}

		return $category;
	}
}

?>

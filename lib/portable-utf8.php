<?php
    
    /**
     * Portable UTF-8
     * Lightweight Library for Unicode Handling in PHP
     * @details    http://pageconfig.com/post/portable-utf8
     * @demo       http://pageconfig.com/post/portable-utf-8-demo
     * 
     * @version    1.2
     * @author     Hamid Sarfraz
     * 
     * @copyright  2013 Hamid sarfraz
     * @license    http://pageconfig.com/post/license
    */
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * utf8_url_slug( )
     * 
     * Creates SEO Friendly URL Slugs with UTF-8 support
     * Optionally can do transliteration
     * @since 1.0
     * 
     * @param    string $str The text which is to be converted Slug
     * @param    int $maxl Optional. Sets the maximum number of characters
     *           to be allowed in the slug. Default is UNLIMITED.
     * @param    bool $trns 
     * @return   string The UTF-8 encoded URL Slug.
    */
    
    function utf8_url_slug( $str = '' , $maxl = -1 , $trns = false )
    {
        $str    = strtolower( $str );
        
        $str    = utf8_clean( $str );
        
        if( $trns && extension_loaded( 'iconv' ) )
        {
            //Transliteration enabled
            
            //This may add some punctuations marks that will be
            //removed in the next step
            
            $str = iconv( 'UTF-8' , 'US-ASCII//TRANSLIT//IGNORE' , $str );
        }
        
        if( pcre_utf8_support( ) )
        {
            //This regex will replace everything other than the following with a hyphen - character
            //1. alphabets and numerics of all languages
            //2. The hyphen - sign
            //3. The underscore _ sign
            
            $str    = preg_replace( '/[^\\p{L}\\p{Nd}\-_]+/u' , '-' , $str );
        }
        else
        {
            //Still Allows Multi Byte characters in Slug
            //But with a difference that Not all the punctuation marks
            //and symbols are removed
            
            //Removing only unsafe characters:    ><+?&"'/\:%#= and all spacers
            
            $str    = preg_replace( '/[\>\<\+\?\&\"\'\/\\\:\s\-\#\%\=]+/' , '-' , $str );
        }
        
        
        if( $maxl > 0 )
        {
            //Cutting the result string down to specified maximum length
            
            $maxl    = ( int ) $maxl;
            
            $str    = utf8_substr( $str , 0 , $maxl );
        }
        
        
        //Removing unnecessary _- from both ends of the string
        
        $str    = trim( $str , '_-' );
        
        if( !strlen( $str ) )
        {
            //True, if
            //1. The $str consisted of all illegal UTF-8 byte sequances (probably injected by users)
            //2. All characters in $str are punctuations and symbols
            //3. You selected to Transliterate, and it went wrong
            
            //But most of the time, this condition should be false
            
            $str    = substr( md5( microtime( true ) ) , 0 , ( $maxl == -1 ? 32 : $maxl ) );
        }
        
        return $str;
    }
    
    
    /**
     * is_utf8( )
     * 
     * Checks whether the passed string contains only byte sequances that
     * appear valid UTF-8 characters.
     * @since 1.0
     * 
     * @param    string $str The string to be checked
     * @return   bool True if the check succeeds, False Otherwise
    */
    
    function is_utf8( $str )
    {
        if( pcre_utf8_support( ) )
        {
            return ( bool ) preg_match( '//u', $str );
        }
        
        
        //Fallback
        
        $len    = strlen( $str );
        
        for( $i = 0 ; $i < $len ; $i++ )
        {
            if( ( $str[$i] & "\x80" ) === "\x00" )
            {
                continue;
            }
            else if( ( ( $str[$i] & "\xE0" ) === "\xC0" ) && ( isset( $str[$i+1] ) ) )
            {
                if( ( $str[$i+1] & "\xC0" ) === "\x80" )
                {
                    $i++;
                    continue;
                }
                
                return false;
            }
            else if( ( ( $str[$i] & "\xF0" ) === "\xE0" ) && ( isset( $str[$i+2] ) ) )
            {
                if( ( ( $str[$i+1] & "\xC0" ) === "\x80" ) && ( ( $str[$i+2] & "\xC0" ) === "\x80" ) )
                {
                    $i    = $i + 2;
                    continue;
                }
                
                return false;
            }
            else if( ( ( $str[$i] & "\xF8" ) === "\xF0" ) && ( isset( $str[$i+3] ) ) )
            {
                if( ( ( $str[$i+1] & "\xC0" ) === "\x80" ) && ( ( $str[$i+2] & "\xC0" ) === "\x80" ) && ( ( $str[$i+3] & "\xC0" ) === "\x80" ) )
                {
                    $i    = $i + 3;
                    continue;
                }
                
                return false;
            }
            else
            {
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * utf8_ord( )
     * 
     * Calculates Unicode Code Point of the given UTF-8 encoded character
     * @since 1.0
     * 
     * @param    string $chr The character of which to calculate Code Point
     * @return   int Unicode Code Point of the given character
     *           0 on invalid UTF-8 byte sequence
    */
    
    function utf8_ord( $chr )
    {
        $chr    = utf8_split( $chr );
        
        $chr    = $chr[0];
        
        switch( strlen( $chr ) )
        {
            case 1:        return
                            ord( $chr );
            
            case 2:        return
                              ( ( ord( $chr[0] ) & 0x1F ) << 6 )
                            | ( ord( $chr[1] ) & 0x3F );
            
            case 3:        return
                              ( ( ord( $chr[0] ) & 0x0F ) << 12 )
                            | ( ( ord( $chr[1] ) & 0x3F ) << 6 )
                            | ( ord( $chr[2] ) & 0x3F );
            
            case 4:        return
                              ( ( ord( $chr[0] ) & 0x07 ) << 18 )
                            | ( ( ord( $chr[1] ) & 0x3F ) << 12 )
                            | ( ( ord( $chr[2] ) & 0x3F ) << 6 )
                            | ( ord( $chr[3] ) & 0x3F );
        }
        
        return 0;
    }
    
    
    /**
     * utf8_strlen( )
     * 
     * Finds the length of the given string in terms of number
     * of valid UTF-8 characters it contains. Invalid characters are ignored.
     * @since 1.0
     * 
     * @param    string $str The string for which to find the character length
     * @return   int Length of the Unicode String
    */
    
    function utf8_strlen( $str )
    {
        //if( pcre_utf8_support( ) )
        //{
        //    return preg_match_all( '/\X/u' , $string , $matches , PREG_SET_ORDER );
        //}
        
        //PCRE code removed because \X is buggy in many recent versions of PHP
        //See the original post.
        
        return count( utf8_split( $str ) );
    }
    
    
    /**
     * utf8_chr( )
     * 
     * Generates a UTF-8 encoded character from the given Code Point
     * @since 1.0
     * 
     * @param    int $code_point The code point for which to generate a character
     * @return   string Milti-Byte character
     *           returns empty string on failure to encode
    */
    
    function utf8_chr( $code_point )
    {
        if( ctype_digit( ( string ) $code_point ) )
        {
            $i    = ( int ) $code_point;
        }
        else
        {
            if( !( $i = ( int ) utf8_unicode_style_to_int( $code_point ) ) )
            {
                return '';
            }
        }
        
        //json not working properly for larger code points
        //See the original post.
        
        //if( extension_loaded( 'json' ) )
        //{
        //  $hex    = dechex( $i );
        //    
        //  return json_decode('"\u'. ( strlen( $hex ) < 4 ? substr( '000' . $hex , -4 ) : $hex ) .'"');
        //}
        //else
        
        if( extension_loaded( 'mbstring' ) )
        {
            return mb_convert_encoding( "&#$i;" , 'UTF-8' , 'HTML-ENTITIES' );
        }
        else if( version_compare( phpversion( ) , '5.0.0' ) === 1 )
        {
            //html_entity_decode did not support Multi-Byte before PHP 5.0.0
            return html_entity_decode( "&#{$i};" , ENT_QUOTES, 'UTF-8' );
        }
        
        
        //Fallback
        
        $bits    = ( int ) ( log( $i , 2 ) + 1 );
        
        if( $bits <= 7 )                //Single Byte
        {
            return chr( $i );
        }
        else if( $bits <= 11 )            //Two Bytes
        {
            return chr( ( ( $i >> 6 ) & 0x1F ) | 0xC0 ) . chr( ( $i & 0x3F ) | 0x80 );
        }
        else if( $bits <= 16 )            //Three Bytes
        {
            return chr( ( ( $i >> 12 ) & 0x0F ) | 0xE0 ) . chr( ( ( $i >> 6 ) & 0x3F ) | 0x80 ) . chr( ( $i & 0x3F ) | 0x80 );
        }
        else if( $bits <=21 )            //Four Bytes
        {
            return chr( ( ( $i >> 18 ) & 0x07 ) | 0xF0 ) . chr( ( ( $i >> 12 ) & 0x3F ) | 0x80 ) . chr( ( ( $i >> 6 ) & 0x3F ) | 0x80 ) . chr( ( $i & 0x3F ) | 0x80 );
        }
        else
        {
            return '';    //Cannot be encoded as Valid UTF-8
        }
    }
    
    
    /**
     * pcre_utf8_support( )
     * 
     * Checks if \u modifier is available that enables Unicode support in PCRE.
     * @since 1.0
     * 
     * @return   bool True if support is available, false otherwise
    */
    
    function pcre_utf8_support( )
    {
        static $support;
        
        if( !isset( $support ) )
        {
            $support = @preg_match( '//u', '' );
            //Cached the response
        }
        
        return $support;
    }
    
    
    /**
     * utf8_clean( )
     * 
     * Accepts a string and removes all non-UTF-8 characters from it.
     * @since 1.0
     * 
     * @param    string $str The string to be sanitized.
     * @return   string Clean UTF-8 encoded string
    */
    
    function utf8_clean( $str )
    {
        //http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
        
        $regx    = '/((?:[\x00-\x7F]|[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3}){1,})|./';
        
        return preg_replace( $regx , '$1' , $str );
    }
    
    
    /**
     * utf8_split( )
     * 
     * Convert a string to an array of Unicode characters.
     * @since 1.0
     * 
     * @param    string $str The string to split into array.
     * @param    int $split_length Max character length of each array element
     * @return   array An array containing chunks of the string
    */
    
    function utf8_split( $str , $split_length = 1 )
    {
        $str    = ( string ) $str;
        
        $ret    = array( );
        
        if( pcre_utf8_support( ) )
        {
            $str    = utf8_clean( $str );
            
            //    http://stackoverflow.com/a/8780076/369005
            $ret    = preg_split('/(?<!^)(?!$)/u', $str );
            
            // \X is buggy in many recent versions of PHP
            //preg_match_all( '/\X/u' , $str , $ret );
            //$ret    = $ret[0];
        }
        else
        {
            //Fallback
            
            $len    = strlen( $str );
            
            for( $i = 0 ; $i < $len ; $i++ )
            {
                if( ( $str[$i] & "\x80" ) === "\x00" )
                {
                    $ret[]    = $str[$i];
                }
                else if( ( ( $str[$i] & "\xE0" ) === "\xC0" ) && ( isset( $str[$i+1] ) ) )
                {
                    if( ( $str[$i+1] & "\xC0" ) === "\x80" )
                    {
                        $ret[]    = $str[$i] . $str[$i+1];
                        
                        $i++;
                    }
                }
                else if( ( ( $str[$i] & "\xF0" ) === "\xE0" ) && ( isset( $str[$i+2] ) ) )
                {
                    if( ( ( $str[$i+1] & "\xC0" ) === "\x80" ) && ( ( $str[$i+2] & "\xC0" ) === "\x80" ) )
                    {
                        $ret[]    = $str[$i] . $str[$i+1] . $str[$i+2];
                        
                        $i    = $i + 2;
                    }
                }
                else if( ( ( $str[$i] & "\xF8" ) === "\xF0" ) && ( isset( $str[$i+3] ) ) )
                {
                    if( ( ( $str[$i+1] & "\xC0" ) === "\x80" ) && ( ( $str[$i+2] & "\xC0" ) === "\x80" ) && ( ( $str[$i+3] & "\xC0" ) === "\x80" ) )
                    {
                        $ret[]    = $str[$i] . $str[$i+1] . $str[$i+2] . $str[$i+3];
                        
                        $i    = $i + 3;
                    }
                }
            }
        }
        
        
        if( $split_length > 1 )
        {
            $ret = array_chunk( $ret , $split_length );
            
            $ret    = array_map( 'implode' , $ret );
        }
        
        return $ret;
    }
    
    
    /**
     * utf8_chunk_split( )
     * 
     * Splits a string into smaller chunks and multiple lines, using the specified
     * line ending character
     * @since 1.0
     * 
     * @param    string $body The original string to be split.
     * @param    int $chunklen The maximum character length of a chunk
     * @param    string $end The character(s) to be inserted at the end of each chunk
     * @return   string The chunked string
    */
    
    function utf8_chunk_split( $body , $chunklen = 76 , $end = "\r\n" )
    {
        return implode( $end , utf8_split( $body , $chunklen ) );
    }
    
    
    
    /**
     * utf8_fits_inside( )
     * 
     * Checks if the number of Unicode characters in a string are not
     * more than the specified integer.
     * @since 1.0
     * 
     * @param    string $str The original string to be checked.
     * @param    int $box_size The size in number of chars to be checked against string.
     * @return   bool true if string is less than or equal to $box_size The
     *           false otherwise
    */
    
    function utf8_fits_inside( $str , $box_size )
    {
        return ( utf8_strlen( $str ) <= $box_size );
    }
    
    
    /**
     * utf8_chr_size_list( )
     * 
     * Generates an array of byte length of each character of a Unicode string.
     * @since 1.0
     * 
     * @param    string $str The original Unicode string
     * @return   array An array of byte lengths of each character.
    */
    
    function utf8_chr_size_list( $str )
    {
        return array_map( 'strlen' , utf8_split( $str ) );
    }
    
    
    /**
     * utf8_max_chr_width( )
     * 
     * Calculates and returns the maximum number of bytes taken by any
     * UTF-8 encoded character in the given string
     * @since 1.0
     * 
     * @param    string $str The original Unicode string
     * @return   array An array of byte lengths of each character.
    */
    
    function utf8_max_chr_width( $str )
    {
        return max( utf8_chr_size_list( $string ) );
    }
    
    
    /**
     * utf8_single_chr_html_encode( )
     * 
     * Converts a UTF-8 character to HTML Numbered Entity like &#123;
     * @since 1.0
     * 
     * @param    string $chr The Unicode character to be encoded as numbered entity
     * @return   string HTML numbered entity
    */
    
    function utf8_single_chr_html_encode( $chr )
    {
        return '&#' . utf8_ord( $chr ) . ';';
    }
    
    
    /**
     * utf8_html_encode( )
     * 
     * Converts a UTF-8 string to a series of
     * HTML Numbered Entities like &#123;&#39;&#1740;...
     * @since 1.0
     * 
     * @param    string $str The Unicode string to be encoded as numbered entities
     * @return   string HTML numbered entities
    */
    
    function utf8_html_encode( $str )
    {
        return implode( array_map( 'utf8_single_chr_html_encode' , utf8_split( $str ) ) );
    }
    
    
    /**
     * utf8_substr( )
     * 
     * UTF-8 aware substr
     * @since 1.0
     * 
     * For detailed documentation see php.net/substr
     * 
     * substr works with bytes, while utf8_substr works with characters
     * and are identical in all other aspects.
    */
    
    function utf8_substr( $str , $start = 0 , $length = NULL )
    {
        //iconv and mbstring are not tolerant to invalid encoding
        //further, their behaviour is inconsistant with PHP's substr
        
        //if( extension_loaded( 'iconv' ) )
        //{
        //    return iconv_substr( $str , $start , $length , 'UTF-8' );
        //}
        //else if( extension_loaded( 'mbstring' ) )
        //{
        //    return mb_substr( $str , $start , $length , 'UTF-8' );
        //}
        
        //Fallback
        
        //Split to array, and remove invalid characters
        $array    = utf8_split( $str );
        
        //Extract relevant part, and join to make sting again
        return implode( array_slice( $array , $start , $length ) );
    }
    
    
    /**
     * utf8_bom( )
     * 
     * Returns the Byte Order Mark Character
     * @since 1.0
     * 
     * @return   string Byte Order Mark
    */
    
    function utf8_bom( )
    {
        return "\xef\xbb\xbf";
        
        //static $bom = 0;
        
        //if( !$bom )
        //{
        //    $bom = pack( 'CCC' , 0xEF , 0xBB , 0xBF );
        //}
        
        //return $bom;
    }
    
    
    /**
     * is_bom( )
     * 
     * Checks if the given string is a Byte Order Mark
     * @since 1.0
     * 
     * @param    string $utf8_chr The input string
     * @return   bool True if the $utf8_chr is Byte Order Mark, False otherwise
    */
    
    function is_bom( $utf8_chr )
    {
        return ( $utf8_chr === utf8_bom( ) );
    }
    
    
    /**
     * utf8_file_has_bom( )
     * 
     * Checks if a file starts with BOM character
     * @since 1.0
     * 
     * @param    string $file_path Path to a valid file
     * @return   bool True if the file has BOM at the start, False otherwise
    */
    
    function utf8_file_has_bom( $file_path )
    {
        return is_bom( file_get_contents( $file_path , 0 , NULL , -1 , 3 ) );
    }
    
    
    /**
     * utf8_string_has_bom( )
     * 
     * Checks if string starts with BOM character
     * @since 1.0
     * 
     * @param    string $str The input string
     * @return   bool True if the string has BOM at the start, False otherwise
    */
    
    function utf8_string_has_bom( $str )
    {
        return is_bom( substr( $str , 0 , 3 ) );
    }
    
    
    /**
     * utf8_add_bom_to_string( )
     * 
     * Prepends BOM character to the string and returns the whole string.
     * If BOM already existed there, the Input string is returned.
     * @since 1.0
     * 
     * @param    string $str The input string
     * @return   string The output string that contains BOM
    */
    
    function utf8_add_bom_to_string( $str )
    {
        if( !is_bom( substr( $str , 0 , 3 ) ) )
        {
            $str    = utf8_bom( ) . $str;
        }
        
        return $str;
    }
    
    
    /**
     * utf8_str_shuffle( )
     * 
     * Shuffles all the characters in the string.
     * @since 1.0
     * 
     * @param    string $str The input string
     * @return   string The shuffled string
    */
    
    function utf8_str_shuffle( $str )
    {
        $array    = utf8_split( $str );
        
        shuffle( $array );
        
        return implode( '' , $array );
    }
    
    
    /**
     * utf8_count_chars( )
     * 
     * Returns count of characters used in a string
     * @since 1.0
     * 
     * @param    string $str The input string
     * @return   array An associative array of Character as keys and
     *           their count as values
    */
    
    function utf8_count_chars( $str )    //there is no $mode parameters
    {
        $array    = array_count_values( utf8_split( $str ) );
        
        ksort( $array );
        
        return $array;
    }
    
    
    
    /**
     * utf8_rev( )
     * 
     * Reverses characters order in the string
     * @since 1.0
     * 
     * @param    string $str The input string
     * @return   string The string with characters in the reverse sequence
    */
    
    function utf8_rev( $str )
    {
        return implode( array_reverse( utf8_split( $str ) ) );
    }
    
    
    /**
     * utf8_strpos( )
     * 
     * Finds the number of Characters to the left of first occurance of the needle
     * @since 1.0
     * 
     * For detailed documentation see php.net/strpos
     * 
     * strpos works with bytes, while utf8_strpos works with characters
     * and are identical in all other aspects.
    */
    
    function utf8_strpos( $haystack , $needle , $offset = 0 )
    {
        if( ( ( int ) $needle ) === $needle && ( $needle >= 0 ) )
        {
            //$needle is an integer and non negative
            
            $needle    = utf8_chr( $needle );
        }
        
        
        $offset    = ( int ) $offset;
        
        if( $offset > 0 )
        {
            $haystack    = utf8_substr( $haystack , $offset );
        }
        
        if( ( $pos = strpos( $haystack , $needle ) ) !== false )
        {
            //$needle found in haystack, just its character position needs to be found now
            
            $left    = substr( $haystack , 0 , $pos );
            
            return $offset + utf8_strlen( $left );
        }
        
        return false;
    }
    
    
    /**
     * utf8_max( )
     * 
     * Returns the UTF-8 character with the maximum code point in the given data
     * @since 1.0
     * 
     * @param    mixed $arg A UTF-8 encoded string or an array of such strings
     * @return   string The character with the highest code point than others
    */
    
    function utf8_max( $arg )
    {
        if( is_array( $arg ) )
        {
            $arg    = implode( $arg );
        }
        
        return utf8_chr( max( utf8_codepoints( $arg ) ) );
    }
    
    
    /**
     * utf8_min( )
     * 
     * Returns the UTF-8 character with the minimum code point in the given data
     * @since 1.0
     * 
     * @param    mixed $arg A UTF-8 encoded string or an array of such strings
     * @return   string The character with the lowest code point than others
    */
    
    function utf8_min( $arg )
    {
        if( is_array( $arg ) )
        {
            $arg    = implode( $arg );
        }
        
        return utf8_chr( min( utf8_codepoints( $arg ) ) );
    }
    
    
    /**
     * utf8_codepoints( )
     * 
     * Accepts a string and returns an array of Unicode Code Points
     * @since 1.0
     * 
     * @param    mixed $arg A UTF-8 encoded string or an array of such strings
     * @param    bool $u_style If True, will return Code Points in U+xxxx format,
     *           default, Code Points will be returned as integers
     * @return   array The array of code points
    */
    
    function utf8_codepoints( $arg , $u_style = false )
    {
        if( is_string( $arg ) )
        {
            $arg    = utf8_split( $arg );
        }
        
        $arg    = array_map( 'utf8_ord' , $arg );
        
        if( $u_style )
        {
            $arg    = array_map( 'utf8_int_to_unicode_style' , $arg );
        }
        
        return $arg;
    }
    
    
    /**
     * utf8_int_to_unicode_style( )
     * 
     * Converts Integer to hexadecimal U+xxxx code point representation
     * @since 1.0
     * 
     * @param    int $int The integer to be converted to hexadecimal code point
     * @return   string The Code Point, or empty string on failure
    */
    
    function utf8_int_to_unicode_style( $int )
    {
        if( ctype_digit( ( string ) $int ) )
        {
            $hex    = dechex( ( int ) $int );
            
            $hex    = ( strlen( $hex ) < 4 ? substr( '0000' . $hex , -4 ) : $hex );
            
            return 'U+'. $hex;
        }
        
        return '';
    }
    
    /**
     * utf8_unicode_style_to_int( )
     * 
     * Opposite to utf8_int_to_unicode_style( )
     * Converts hexadecimal U+xxxx code point representation to Integer
     * @since 1.0
     * 
     * @param    string $str The Hexadecimal Code Point representation
     * @return   int The Code Point, or 0 on failure
    */
    
    function utf8_unicode_style_to_int( $str )
    {
        if( preg_match( '/^U\+[a-z0-9]{4,6}$/i' , $str ) )
        {
            return hexdec( substr( $str , 2 ) );
        }
        
        return 0;
    }
    
    
    /**
     * utf8_chr_to_unicode_style( )
     * 
     * Get hexadecimal code point (U+xxxx) of a UTF-8 encoded character
     * @since 1.0
     * 
     * @param    string $chr The input character
     * @return   string The Code Point encoded as U+xxxx
    */
    
    function utf8_chr_to_unicode_style( $chr )
    {
        return utf8_int_to_unicode_style( utf8_ord( $chr ) );
    }
    
    
    /**
     * utf8_word_count( )
     * 
     * Counts number of words in the UTF-8 string
     * @since 1.0
     * 
     * @param    string $str The input string
     * @return   int The number of words in the string
    */
    
    function utf8_word_count( $str )
    {
        return count( explode( '-' , utf8_url_slug( $str ) ) );
    }
    
    
    
    
    
    
    
    //Since Version 1.2
    
    
    /**
     * utf8_string( )
     * 
     * Makes a UTF-8 string from Code  points
     * @since 1.2
     * 
     * @param    array $array Integer or Hexadecimal codepoints
     * @return   string UTF-8 encoded string
    */
    
    function utf8_string( $array )
    {
        return implode( array_map( 'utf8_chr' , $array ) );
    }
    
    
    /**
     * utf8_substr_count( )
     * 
     * Count the number of sub string occurances
     * @since 1.2
     * 
     * @param    string $haystack The string to search in
     * @param    string $needle The string to search for
     * @param    int $offset The offset where to start counting
     * @param    int $length The maximum length after the specified offset to search for the substring.
     * @return   int number of occurances of $needle
    */
    
    function utf8_substr_count( $haystack , $needle , $offset = 0 , $length = NULL )
    {
        if( $offset || $length )
        {
            $haystack    = utf8_substr( $haystack , $offset , $length );
        }
        
        return ( $length === null ? substr_count( $haystack , $needle , $offset ) : substr_count( $haystack , $needle , $offset , $length ) );
    }
    
    
    /**
     * is_ascii( )
     * 
     * Checks if a string is 7 bit ASCII
     * @since 1.2
     * 
     * @param    string $str The string to check
     * @return   bool True if ASCII, False otherwise
    */
    
    function is_ascii( $str )
    {
        return ( bool ) !preg_match( '/[\x80-\xff]/' , $str );
    }
    
    
    /**
     * utf8_range( )
     * 
     * Create an array containing a range of UTF-8 characters
     * @since 1.2
     * 
     * @param    mixed $var1 Numeric or hexadecimal code points, or a UTF-8 character to start from
     * @param    mixed $var2 Numeric or hexadecimal code points, or a UTF-8 character to end at
     * @return   array Array of UTF-8 characters
    */
    
    function utf8_range( $var1 , $var2 )
    {
        if( ctype_digit( ( string ) $var1 ) )
        {
            $start    = ( int ) $var1;
        }
        else if( !( $start = ( int ) utf8_unicode_style_to_int( $var1 ) ) )
        {
            //if not u+0000 style codepoint
            
            if( !( $start    = utf8_ord( $var1 ) ) )
            {
                //if not a valid utf8 character
                
                return array( );
            }
        }
        
        
        if( ctype_digit( ( string ) $var2 ) )
        {
            $end    = ( int ) $var2;
        }
        else if( !( $end = ( int ) utf8_unicode_style_to_int( $var2 ) ) )
        {
            //if not u+0000 style codepoint
            
            if( !( $end    = utf8_ord( $var1 ) ) )
            {
                //if not a valid utf8 character
                
                return array( );
            }
        }
        
        return array_map( 'utf8_chr' , range( $start , $end ) );
    }
    
    
    /**
     * utf8_hash( )
     * 
     * Creates a random string of UTF-8 characters
     * @since 1.2
     * 
     * @param    int $len The length of string in characters
     * @return   string String consisting of random characters
    */
    
    function utf8_hash( $len = 8 )
    {
        Static $chrs        = array( );
        Static $chrs_len    = null;
        
        if( !$chrs )
        {
            if( pcre_utf8_support( ) )
            {
                $chrs    = array_map( 'utf8_chr' , range( 48 , 0xffff ) );
                
                $chrs    = preg_replace( '/[^\p{N}\p{Lu}\p{Ll}]/u' , '' , $chrs );
                
                $chrs    = array_values( array_filter( $chrs ) );
            }
            else
            {
                $chrs    = array_merge( range( '0' , '9' ) , range( 'A' , 'Z' ) , range( 'a' , 'z' ) );
            }
            
            $chrs_len    = count( $chrs );
        }
        
        
        $hash    = '';
        
        for( ; $len ; --$len )
        {
            $hash .= $chrs[ mt_rand( ) % $chrs_len ];
        }
        
        return $hash;
    }
    
    
    /**
     * utf8_chr_map( )
     * 
     * Applies callback to all characters of a string
     * @since 1.2
     * 
     * @param    string $str UTF-8 string to run callback on
     * @param    string $callback The callback function
     * @return   array The outcome of callback
    */
    
    function utf8_chr_map( $str , $callback )
    {
        $chrs    = utf8_split( $str );
        
        return array_map( $callback , $chars );
    }
    
    
    /**
     * utf8_callback( )
     * 
     * @Alias of utf8_chr_map( )
     * @since 1.2
    */
    
    function utf8_callback( $str , $callback )
    {
        return utf8_chr_map( $str , $callback );
    }
    
    
    /**
     * utf8_access( )
     * 
     * Returns a single UTF-8 character from string.
     * @since 1.2
     * 
     * @param    string $str UTF-8 string
     * @param    int $pos The position of character to return.
     * @return   string Single Multi-Byte character
    */
    
    function utf8_access( $string , $pos )
    {
        //return the character at the specified position: $str[1] like functionality
        
        return utf8_substr( $string , $pos , 1 );
    }
    
    
    /**
     * utf8_str_sort( )
     * 
     * Sort all characters according to code points
     * @since 1.2
     * 
     * @param    string $str UTF-8 string
     * @param    bool $unique Sort unique. If true, repeated characters are ignored
     * @param    bool $desc If true, will sort characters in reverse code point order.
     * @return   string String of sorted characters
    */
    
    function utf8_str_sort( $str , $unique = false , $desc = false )
    {
        $array    = utf8_codepoints( $str );
        
        if( $unique )
        {
            $array    = array_flip( array_flip( $array ) );
        }
        
        if( $desc )
        {
            arsort( $array );
        }
        else
        {
            asort( $array );
        }
        
        return utf8_string( $array );
    }
    
    
    /**
     * utf8_strip_tags( )
     * 
     * Strip HTML and PHP tags from a string
     * @since 1.2
     * 
     * @param    string $str UTF-8 string
     * @param    string $allowable_tags The tags to allow in the string.
     * @return   string The stripped string.
    */
    
    function utf8_strip_tags( $string , $allowable_tags = '' )
    {
        //clean broken utf8
        $string = utf8_clean( $string );
        
        return strip_tags( $string , $allowable_tags );
    }
    
    
    
    
    
?>

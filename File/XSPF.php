<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +---------------------------------------------------------------------------+
// | File_XSPF PEAR Package for Manipulating XSPF Playlists                    |
// | Copyright (c) 2005 David Grant <david@grant.org.uk>                       |
// +---------------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU Lesser General Public                |
// | License as published by the Free Software Foundation; either              |
// | version 2.1 of the License, or (at your option) any later version.        |
// |                                                                           |
// | This library is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU         |
// | Lesser General Public License for more details.                           |
// |                                                                           |
// | You should have received a copy of the GNU Lesser General Public          |
// | License along with this library; if not, write to the Free Software       |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA |
// +---------------------------------------------------------------------------+

/**
 * PHP version 4
 * 
 * @author      David Grant <david@grant.org.uk>
 * @copyright   Copyright (c) 2005 David Grant
 * @license     http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @link        http://www.xspf.org/
 * @package     File_XSPF
 * @version     CVS: $Id$
 */

/**
 * 
 */
require_once 'PEAR.php';

require_once 'File/XSPF/Extension.php';
require_once 'File/XSPF/Handler.php';
require_once 'File/XSPF/Identifier.php';
require_once 'File/XSPF/Link.php';
require_once 'File/XSPF/Location.php';
require_once 'File/XSPF/Meta.php';
require_once 'File/XSPF/Track.php';

require_once 'Validate.php';

require_once 'XML/Parser.php';
require_once 'XML/Tree.php';

/**
 * Constant to identify an attribution as a location element.
 * 
 * This constant may be passed as the second argument to the
 * {@link File_XSPF::addAttribution()} method of this class to
 * signify that the passed data is a location element.
 * 
 * @link    File_XSPF::addAttribution()
 */
define("FILE_XSPF_ATTRIBUTION_LOCATION",   1);
/**
 * Constant to identify an attribution as an identifier element.
 * 
 * This constant may be passed as the second argument to the
 * {@link File_XSPF::addAttribution()} method of this class to
 * signify that the passed data is an identifier element.
 * 
 * @link    File_XSPF::addAttribution()
 */
define('FILE_XSPF_ATTRIBUTION_IDENTIFIER', 2);

/**
 * This constant signifies an error closing a file.
 */
define('FILE_XSPF_ERROR_FILE_CLOSURE',    1);
/**
 * This constant signifies an error opening a file.
 */
define('FILE_XSPF_ERROR_FILE_OPENING',    2);
/**
 * This constant signfies an error writing to a file.
 */
define('FILE_XSPF_ERROR_FILE_WRITING',    3);

/**
 * This is the main class for this package.
 * 
 * This class serves as the central point for all other classes in this
 * package, and provides the majority of manipulative methods for outputting
 * the XSPF playlist.
 * 
 * @example     examples/example_1.php  Generating a One Track Playlist
 * @example     examples/example_2.php  Filtering an Existing Playlist
 * @example     examples/example_3.php  Cataloging a Music Collection
 * @example     examples/example_4.php  Retrieving Statistics from Audioscrobbler
 * @package     File_XSPF
 */
class File_XSPF
{   
    /**
     * A human-readable comment on this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_annotation;
    /**
     * A multi-dimensional array of location and identifier elements.
     *
     * @access  private
     * @var     array
     */
    var $_attributions  = array();
    /**
     * Human-readable name of the entity responsible for this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_creator;
    /**
     * Creation date of this playlist in XML schema dateTime format.
     *
     * @access  private
     * @var     string
     */
    var $_date;
    /**
     * An array of File_XSPF_Extension instances.
     *
     * @access  private
     * @var     array
     */
    var $_extensions    = array();
    /**
     * Canonical ID for this playlist as a URN.
     *
     * @access  private
     * @var     string
     */
    var $_identifier;
    /**
     * The URL of an image to display in default of a track image.
     *
     * @access  private
     * @var     string
     */
    var $_image;
    /**
     * The URL of a web page to find out more about this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_info;
    /**
     * The URL of the license for this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_license;
    /**
     * An array of File_XSPF_Link instances.
     *
     * @access  private
     * @var     array
     */
    var $_links         = array();
    /**
     * The source URL of this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_location;
    /**
     * An array of File_XSPF_Meta instances.
     *
     * @access  private
     * @var     array
     */
    var $_meta          = array();
    /**
     * An array of File_XSPF_Track instances.
     *
     * @access  private
     * @var     array
     */
    var $_tracks        = array();
    /**
     * The human-readable title of this playlist.
     *
     * @access  private
     * @var     string
     */
    var $_title;
    /**
     * The version of XSPF specification being used.
     *
     * @access  private
     * @var     int
     */
    var $_version       = 1;
    /**
     * The namespace definition for this format.
     *
     * @access  private
     * @var     string
     */
    var $_xmlns         = "http://xspf.org/ns/0/";
    
    /**
     * Creates a new File_XSPF object.
     * 
     * This constructor optionally takes a file path from which to read an
     * existing XSPF file.  If no path is provided, the constructor will
     * build the minimum object required by the XSPF specification.
     *
     * @access  public
     * @param   string  $path the path to an XSPF file.
     * @return  mixed   an instance of File_XSPF if succesful, or PEAR_Error if unsuccessful.
     * @throws  XML_Parser_Error
     */
    function File_XSPF($path = null)
    {
        if (is_null($path))
            return $this;

        $parser =& new XML_Parser();
        $handle =& new File_XSPF_Handler($this);

        $result = $parser->setInputFile($path);
        if (PEAR::isError($result)) {
            return PEAR::raiseError($result->getMessage(), $result->getCode());
        }
        $parser->setHandlerObj($handle);
        $result = $parser->parse();
        if (PEAR::isError($result)) {
            return PEAR::raiseError($result->getMessage(), $result->getCode());
        }
        return $this;
    }
    
    /**
     * Write out an XML tag for the specified property.
     * 
     * This method returns a string containing a well-formed XML element for the
     * specified property.  If the element contains no data, an empty string is
     * returned.
     *
     * @access  private
     * @param   string  $element the element required to be written.
     * @return  string  the well-formatted XML tag for the provided element.
     */
    function _writeElement($element, $depth = 1)
    {
        if (! is_null($this->{'_' . $element}))
            return ('<' . $element . '>' . $this->{'_' . $element} . '</' . $element . '>');
        else
            return '';
    }
    
    /**
     * Add an identifier or location tag to the playlist attribution.
     * 
     * This method adds a identifier or location tag to the playlist 
     * attribution.  The first parameter must be an instance of either the
     * File_XSPF_Identifier or File_XSPF_Location classes.
     * 
     * The third parameter, $append, affects the output of the order of the 
     * children of the attribution element.  According to the specification, the
     * children of the attribution element should be in chronological order, so
     * this parameter is included to make the job somewhat more simplistic.
     *
     * @access  public
     * @param   object  $attribution    an instance of File_XSPF_Identifier or File_XSPF_Location.
     * @param   int     $type           the type of attribution element.
     * @param   boolean $append         true to append, or false to prepend.
     * @see     File_XSPF::getLicense()
     */
    function addAttribution($attribution, $append = TRUE)
    {
        if ($append) {
            array_push($this->_attributions, $attribution);
        } else {
            array_unshift($this->_attributions, $attribution);
        }
    }
    
    /**
     * Add an extension element to the playlist.
     * 
     * This method adds an extension element to the playlist.  This function 
     * will only accept instances of the File_XSPF_Extension class, which is 
     * documented elsewhere.
     *
     * @access  public
     * @param   File_XSPF_Extension $extension an instance of File_XSPF_Extension
     */
    function addExtension($extension)
    {
        if (is_object($extension) && strtolower(get_class($extension)) == "file_xspf_extension") {
            $this->_extensions[] = $extension;
        }
    }
    
    /**
     * Add a link element to the playlist.
     * 
     * This method adds a link element to the playlist.  The $link parameter 
     * must be a instance of the {@link File_XSPF_Link File_XSPF_Link} class or 
     * the method will fail.
     *
     * @access  public
     * @param   File_XSPF_Link $link    an instance of File_XSPF_Link
     */
    function addLink($link)
    {
        if (is_object($link) && strtolower(get_class($link)) == "file_xspf_link") {
            $this->_links[] = $link;
        }
    }
    
    /**
     * Add a meta element to the playlist.
     * 
     * This method adds a meta element to the playlist.  The $meta parameter 
     * must be an instance of the {@link File_XSPF_Meta File_XSPF_Meta} class or
     * the method will fail.
     *
     * @access  public
     * @param   File_XSPF_Meta $meta    an instance of File_XSPF_Meta.
     */
    function addMeta($meta)
    {
        if (is_object($meta) && strtolower(get_class($meta)) == "file_xspf_meta") {
            $this->_meta[] = $meta;
        }
    }
    
    /**
     * Add a track element to the playlist.
     * 
     * This method adds a track element to the playlist.  Complimentary 
     * documentation exists for the {@link File_XSPF_Track File_XSPF_Track}
     * class, and should be the focus of the majority of attention for users 
     * building a XSPF playlist.
     *
     * @access  public
     * @param   File_XSPF_Track $track  an instance of File_XSPF_Track.
     */
    function addTrack($track)
    {
        if (is_object($track) && strtolower(get_class($track)) == "file_xspf_track") {
            $this->_tracks[] = $track;
        }
    }
    
    /**
     * Get the annotation for this playlist.
     * 
     * This method returns the contents of the annotation element, which
     * is the human-readable comment of this playlist.
     *
     * @access  public
     * @return  string the annotation data for this playlist.
     */
    function getAnnotation()
    {
        return $this->_annotation;
    }
    
    /**
     * Get an array of attribution elements.
     * 
     * This method returns an array of attribution elements.
     *
     * @access  public
     * @param   int $offset the offset of the attribution to retrieve.
     * @return  File_XSPF_Identifier|File_XSPF_Location an instance of either File_XSPF_Identifier or File_XSPF_Location
     * @see     File_XSPF::getLicense()
     */
    function getAttribution($offset = 0)
    {
        if (isset($this->attributions[$offset])) {
            return $this->_attributions[$offset];
        }
    }
    
    /**
     * Get an array of attribution elements.
     *
     * This method returns a list of attribution elements, which is either an instance
     * of File_XSPF_Identifier or File_XSPF_Location.
     * 
     * @access  public
     * @return  array
     */
    function getAttributions($filter = null)
    {
        if (is_null($filter)) {
            return $this->_attributions;
        } else {
            $attributions = array();
            foreach ($this->_attributions as $attribution) {
                if ($filter & FILE_XSPF_ATTRIBUTION_IDENTIFIER && strtolower(get_class($attribution)) == 'file_xspf_identifier') {
                    $attributions[] = $attribution;
                } elseif ($filter & FILE_XSPF_ATTRIBUTION_LOCATION && strtolower(get_class($attribution)) == 'file_xspf_location') {
                    $attributions[] = $attribution;
                }
            }
            return $attributions;
        }   
    }
    
    /**
     * Get the author of this playlist.
     * 
     * This method returns the contents of the creator element, which
     * represents the author of this playlist.
     *
     * @access  public
     * @return  string  the creator of this playlist as a human-readable string.
     */
    function getCreator()
    {
        return $this->_creator;
    }

    /**
     * Get the date of creation for this playlist.
     * 
     * This method returns the date on which this playlist was created (not
     * last modified), formatted as a XML schema dateTime, which is the same as
     * the 'r' parameter for {@link http://php.net/date date()} in PHP5.
     *
     * @access  public
     * @return  string  a XML schema dateTime formatted date.
     */
    function getDate()
    {
        return $this->_date;
    }
    
    /**
     * Get the duration of this playlist in seconds.
     * 
     * This method returns the length of this playlist in seconds.  These times
     * are taken from the duration elements of the playlist track elements.
     *
     * @access  public
     * @return  int the length in seconds of this playlist.
     */
    function getDuration()
    {
        $duration = 0;
        foreach ($this->_tracks as $track) {
            $duration += $track->getDuration();
        }
        return (floor($duration / 1000));
    }
    
    /**
     * Get an identifier for this playlist.
     * 
     * This method returns a canonical ID for this playlist as a URN.  An 
     * example might be an SHA1 hash of the tracklisting, e.g.
     * sha1://0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33
     *
     * @access  public
     * @return  string a valid URN for identifing this playlist.
     */
    function getIdentifier()
    {
        return $this->_identifier;
    }
    
    /**
     * Get the image URL for this playlist.
     * 
     * This method returns the URL of the image used to represent this
     * playlist.  This image should be used if individual tracks belonging 
     * to this playlist do not have their own image.
     *
     * @access  public
     * @return  string the URL of the image for this playlist.
     */
    function getImage()
    {
        return $this->_image;
    }
    
    /**
     * Get the URL of a web page containing information about this playlist.
     * 
     * This method returns the URL of a web page, allowing the user to find
     * out more information about the author of the playlist, and find other
     * playlists.
     *
     * @access  public
     * @return  string a URL containing information about this playlist.
     */
    function getInfo()
    {
        return $this->_info;
    }
    
    /**
     * Get the license for this playlist.
     * 
     * This method returns the URL of the license under which this playlist has 
     * been or will be released, such as http://www.gnu.org/copyleft/lesser.html
     * for the LGPL.  If the specified license contains a requirement for 
     * attribution, users should use the 
     * {@link File_XSPF::getAttribution() getAttribution} method to retrieve an 
     * array of attributions.
     *
     * @access  public
     * @link    File_XSPF::getAttribution()
     * @return  string  the URL of the license for this playlist.
     */
    function getLicense()
    {
        return $this->_license;
    }
    
    /**
     * Get an array of link elements for this playlist.
     * 
     * This method returns a list of link elements, which contain non-XSPF web
     * resources, which still relate to this playlist.
     *
     * @access  public
     * @return  array   an array of File_XSPF_Link instances.
     * @see     File_XSPF::getMeta()
     */
    function getLink()
    {
        return $this->_links;
    }
    
    /**
     * Get the source URL for this playlist.
     * 
     * This methods returns the URL where this playlist may be found, such as
     * the path to an FTP or HTTP server, or perhaps the path to a file on
     * the users local machine.
     *
     * @access  public
     * @return  string  the URL where this playlist may be found.
     */
    function getLocation()
    {
        return $this->_location;
    }
    
    /**
     * Get an array of non-XSPF metadata.
     * 
     * This method returns an array of meta elements associated with this
     * playlist.  Meta elements contain metadata not covered by the XSPF
     * specification without breaking XSPF validation.
     *
     * @access  public
     * @return  array   an array of File_XSPF_Meta instances.
     * @see     File_XSPF::getLink()
     */
    function getMeta()
    {
        return $this->_meta;
    }
    
    /**
     * Get the human-readable title of this playlist.
     * 
     * This method returns the human-readable title of this playlist, which may
     * be a simple reference to what the playlist contains, e.g. "Favourites".
     *
     * @access  public
     * @return  string  the human-readable title of this playlist.
     */
    function getTitle()
    {
        return $this->_title;
    }
    
    /**
     * Get an array of tracks for this playlist.
     * 
     * This method returns an array of {@link File_XSPF_Track File_XSPF_Track}
     * objects belonging to this playlist, which directly represent individual
     * tracks on this playlist.
     *
     * @access  public
     * @return  array   an array of File_XSPF_Track instances.
     * @see     File_XSPF_Track
     */
    function getTracks()
    {
        return $this->_tracks;
    }

    /**
     * Set an annotation for this playlist.
     * 
     * This method sets an annotation, or human-readable description of this
     * playlist, e.g. "All the Radiohead tracks in my vast collection."
     *
     * @access  public
     * @param   string $annotation a human-readable playlist description.
     */
    function setAnnotation($annotation)
    {
        $this->_annotation = $annotation;
    }
    
    /**
     * Set the creator of this playlist.
     * 
     * The method sets the creator element of this playlist, which is the
     * human-readable name of the author of the resource, such as a person's
     * name, or a company, or a group.
     *
     * @access  public
     * @param   string $creator the name of the creator of this playlist.
     */
    function setCreator($creator)
    {
        $this->_creator = $creator;
    }
    
    /**
     * Set the creation date of this playlist.
     * 
     * This method sets the creation date (not last-modified date) of this
     * playlist.  If the $date parameter contains only digits, this method will
     * assume it is a timestamp, and format it accordingly.
     *
     * @access  public
     * @param   mixed $date either an XML schema dateTime or UNIX timestamp.
     */
    function setDate($date)
    {
        if (ctype_digit($date)) {
            if (version_compare(phpversion(), '5') != -1) {
                $this->_date = date('r', $date);
            } else {
                $this->_date = date('Y-m-d\TH:i:sO', $date);
            }
        } else {
            $this->_date = $date;
        }
    }
    
    /**
     * Set the identifier for this playlist.
     * 
     * This method sets an identifier for this playlist, such as a SHA1 hash
     * of the track listing.  The $identifier must be a valid URN.
     *
     * @access  public
     * @param   string $identifier the URN of a resource to identify this playlist.
     */
    function setIdentifier($identifier)
    {
        if (File_XSPF::_validateURN($identifier)) {
            $this->_identifier = $identifier;
        }
    }
        
    /**
     * Set the image URL for this playlist.
     * 
     * This method sets the image URL for this playlist, which provides a
     * fallback image if individual tracks do not themselves have image URLs
     * set.
     *
     * @access  public
     * @param   string $image  the URL to an image resource.
     */
    function setImage($image)
    {
        if (File_XSPF::_validateURL($image)) {
            $this->_image = $image;
        }
    }

    /**
     * Set the URL of web page for this playlist.
     * 
     * This method sets the URL of a web page containing information about this
     * playlist, and possibly links to other playlists by the same author.
     *
     * @access  public
     * @param   string $info the URL of a web page to describe this playlist.
     */
    function setInfo($info)
    {
        if (File_XSPF::_validateURL($info)) {
            $this->_info = $info;
        }
    }
    
    /**
     * Set the license for this playlist.
     * 
     * This method sets the URL of the license under which this playlist
     * was released.  If the license requires attribution, such as some
     * Creative Commons licenses, such attributions can be added using
     * the {@link File_XSPF::addAttribution() addAttribution} method.
     *
     * @access  public
     * @see     File_XSPF::addAttribution()    
     * @param   string $license The URL of the license for this playlist.
     */
    function setLicense($license)
    {
        if (File_XSPF::_validateURL($license)) {
            $this->_license = $license;
        }
    }
    
    /**
     * Set the source URL of this playlist.
     * 
     * This method sets the source URL of this playlist.  For example, if
     * one offered one's playlists for syndication over the Internet, one 
     * might add a URL to direct users to the original, such as
     * http://www.example.org/list.xspf.
     *
     * @access  public
     * @param   string $location the source URL of this playlist.
     */
    function setLocation($location)
    {
        if (File_XSPF::_validateURL($location)) {
            $this->_location = $location;
        }
    }
    
    /**
     * Set the title of this playlist.
     * 
     * This method sets the human-readable title of this playlist.  For example
     * one might call a playlist 'Favourites', or the name of a band.
     *
     * @access  public
     * @param   string $title the human-readable title of this playlist.
     */
    function setTitle($title)
    {
        $this->_title = $title;
    }
    
    /**
     * Validate a URI.
     * 
     * This method validates a URI against the allowed schemes for this class.
     *
     * @access  private
     * @param   string  $uri a URI to test for validity.
     * @return  boolean true if valid, false otherwise.
     */
    function _validateUri($uri)
    {
        return (Validate::uri($uri));
    }
    
    /**
     * Validate a URL
     * 
     * This method validates a URL, such as http://www.example.org/.
     *
     * @access  private
     * @param   string $url a URL to test for validity.
     * @return  boolean true if valid, false otherwise.
     */
    function _validateUrl($url)
    {
        return (Validate::uri($url, array('allowed_schemes' => array('file', 'ftp', 'http', 'https'))));
    }
    
    /**
     * Validate a URN.
     * 
     * This method validates a URN, such as md5://8b1a9953c4611296a827abf8c47804d7
     *
     * @access  private
     * @param   string $urn a URN to test for validity.
     * @return  boolean true if valid, false otherwise.
     */
    function _validateUrn($urn)
    {
        return (Validate::uri($urn));
    }
    
    /**
     * Save this playlist to a file.
     * 
     * This method outputs this playlist to a file, or any other location that 
     * can be written to by fopen and fwrite.  If the file write is successful,
     * this function will return true, otherwise it will return an instance of a
     * PEAR_Error object.
     *
     * @access  public
     * @param   string $filename the file to which to write this XSPF playlist.
     * @return  mixed either true for success, or an instance of PEAR_Error.
     * @throws  PEAR_Error
     */
    function toFile($filename)
    {
        $fp = @fopen($filename, "w");
        if (! $fp) {
            return (PEAR::raiseError("Could Not Open File", FILE_XSPF_ERROR_FILE_OPENING));
        }
        if (! fwrite($fp, $this->toString())) {
            return (PEAR::raiseError("Writing to File Failed", FILE_XSPF_ERROR_FILE_WRITING));
        }
        if (! fclose($fp)) {
            return (PEAR::raiseError("Failed to Close File", FILE_XSPT_ERROR_FILE_CLOSURE));
        }
        return TRUE;
    }
    
    /**
     * Save this playlist as an M3U playlist.
     * 
     * This method saves the current XSPF playlist in M3U format, providing a one-way
     * conversion to the popular flat file playlist.  Reverse conversion is considered
     * to be beyond the scope of this package.
     *
     * @access  public
     * @param   string $filename the file to which to write the M3U playlist.
     * @return  mixed either true for success or an instance of PEAR_Error
     * @throws  PEAR_Error
     */
    function toM3U($filename)
    {
        $fp = @fopen($filename, "w");
        if (! $fp) {
            return (PEAR::raiseError("Could Not Open File", FILE_XSPF_ERROR_FILE_OPENING));
        }
        foreach ($this->_tracks as $track) {
            $locations = $track->getLocation();
            foreach ($locations as $location) {
                if (! fwrite($fp, $location . "\n")) {
                    return (PEAR::raiseError("Writing to File Failed", FILE_XSPF_ERROR_FILE_WRITING));
                }
            }
        }
        if (! fclose($fp)) {
            return (PEAR::raiseError("Failed to Close File", FILE_XSPT_ERROR_FILE_CLOSURE));
        }
        return TRUE;
    }
    
    /**
     * Save this playlist as SMIL format.
     * 
     * This method saves this XSPF playlist as a SMIL file, which can be used as a playlist.
     * This is a one-way conversion, as reading SMIL files is considered beyond the scope
     * of this application.
     *
     * @access  public
     * @param   string  $filename the file to which to write the SMIL playlist.
     * @return  mixed   either true if successful, or an instance of PEAR_Error
     * @throws  PEAR_Error
     */
    function toSMIL($filename)
    {
        $tree =& new XML_Tree();
        $root =& $tree->addRoot('smil');
        $body =& $root->addChild('body');
        $seq  =& $body->addChild('seq');

        foreach ($this->_tracks as $track) {
            $locations = $track->getLocation();
            foreach ($locations as $location) {
                if ($tracl->getAnnotation()) {
                    $seq->addChild('audio', '', array('title' => $track->getAnnotation(), 'url' => $location));
                } else {
                    $seq->addChild('audio', '', array('url' => $location));
                }   
            }
        }

        $fp = @fopen($filename, "w");
        if (! $fp) {
            return (PEAR::raiseError("Could Not Open File", FILE_XSPF_ERROR_FILE_OPENING));
        }
        if (! fwrite($fp, $tree->get())) {
            return (PEAR::raiseError("Writing to File Failed", FILE_XSPF_ERROR_FILE_WRITING));
        }
        if (! fclose($fp)) {
            return (PEAR::raiseError("Failed to Close File", FILE_XSPT_ERROR_FILE_CLOSURE));
        }
        return TRUE;
    }
    
    /**
     * Output this playlist as a stream.
     * 
     * This method outputs this playlist as a HTTP stream with a content type
     * of 'application/xspf+xml', which could be passed off by a user agent to a
     * XSPF-aware application.
     *
     * @access  public
     */
    function toStream()
    {
        header("Content-type: application/xspf+xml");
        print $this->toString();
    }
    
    /**
     * Output this playlist as a string.
     * 
     * This method outputs this playlist as a string using the XML_Tree package.
     *
     * @access  public
     * @return  string this playlist as a valid XML string.
     */
    function toString()
    {
        $tree =& new XML_Tree();
        $root =& $tree->addRoot('playlist', '', array('version' => $this->_version, 'xmlns' => $this->_xmlns));
        if ($this->_annotation) {
            $root->addChild('annotation', $this->getAnnotation());
        }
        if (count($this->_attributions)) {
            $attr =& $root->addChild('attribution');
            foreach ($this->_attributions as $attribution) {
                $attribution->_toXml($attr);
            }
        }
        if ($this->_creator) {
            $root->addChild('creator', $this->getCreator());
        }
        if ($this->_date) {
            $root->addChild('date', $this->getDate());
        }
        if (count($this->_extensions)) {
            foreach ($this->_extensions as $extension) {
                $extension->_toXml($root);
            }   
        }
        if ($this->_identifier) {
            $root->addChild('identifier', $this->getIdentifier());
        }
        if ($this->_image) {
            $root->addChild('image', $this->getImage());
        }
        if ($this->_info) {
            $root->addChild('info', $this->getInfo());
        }
        if ($this->_license) {
            $root->addChild('license', $this->getLicense());
        }
        if (count($this->_links)) {
            foreach ($this->_links as $link) {
                $link->_toXml($root);
            }   
        }
        if ($this->_location) {
            $root->addChild('location', $this->getLocation());
        }
        if ($this->_title) {
            $root->addChild('title', $this->getTitle());
        }
        if (count($this->_tracks)) {
            $tracklist =& $root->addChild('trackList');
            foreach ($this->_tracks as $track) {
                $track->_toXml($tracklist);
            }   
        }
        return $tree->get();
    }
}
?>
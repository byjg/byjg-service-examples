<?php
/**
* SMS PIXXIS - Componente para envio de SMS no CMS JOOMLA
*
* Ideia Original: Axel Sauerhoefer < mysms[at]quelloffen.com >
* SMS PIXXIS foi desenvolvido por Claudio Eden < sms[at]pixxis.com.br >
* http://www.pixxis.com.br
*
* Todos os direitos reservados. 
*
* @license http://www.gnu.org/licenses/lgpl.html GNU/LGPL
* SMS PIXXIS eh um software livre. Esta versao pode ter sido modificado nos termos da 
* LGPL (Library ou Lesser General Public License), e como eh distribuida inclui ou eh derivado de 
* obras licenciado sob a Licenca Publica Geral GNU ou outras licencas de software livre ou open source
*
* Este programa e distribuido na esperanca que seja util, mas SEM QUALQUER GARANTIA, 
* sem mesmo a garantia implicita de COMERCIALIZACAO ou ADEQUACAO PARA UM DETERMINADO PROPOSITO.
*
**/

//check if joomla call us
defined( '_JEXEC' ) or die( 'Restricted access' );

if( defined( 'PIXXIS_BACKEND_GROUPS_PHP' ) == true )
{
  return;
}

/**
 * Define our class constant to precent multipe definition
 */
define( 'PIXXIS_BACKEND_GROUPS_PHP', 1 );

/**
*  Pixxis Group class
*
 * @package Pixxis
 * @subpackage Util
**/
class PixxisGroup {

      var $_id; //id of the group unique
      var $_ownerID; //owner id
      var $_name; //group name
      var $_members;//group members
	  var $_db; //refernce to the global db object

/**
* The constructor creates a new user group
*
**/

function PixxisGroup()
{
    $this->_id = -99;
    $this->_name = '';
    $this->_members = array();
    $this->_db = &JFactory::getDBO();
}

function init($idORName)
{
	//prevent null values
 	if( is_null($idORName ) )
 	{
	   	return;
 	}

 	if( is_numeric($idORName) )
 	{
   		$sql = "SELECT COUNT(*) AS COUNT from #__pixxis_groups WHERE id='$idORName'";
 	}else{
   		$sql = "SELECT COUNT(*) AS COUNT from #__pixxis_groups WHERE name='$idORName'";
 	}

 	$this->_db->setQuery($sql);

  	if( $this->_db->query() === false )
  	{
        PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
        die;
    }

  	$g = $this->_db->loadObject();
  //we can only create our group if we have a name, checkup

  	if( !is_numeric( $idORName ) )
  	{
      //group not exists, create it, and call us again
      if( $g->COUNT <= 0 )
      {

      	  $my = &JFactory::getUser();
      	  $id = $my->get('id');

          $sql = "INSERT INTO #__pixxis_groups VALUES(0, '$idORName', $id )";
          $this->_db->setQuery($sql);

          if( $this->_db->query() === false )
          {
             PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
             die;
          }
       }
   }

   if( is_numeric( $idORName ) )
   {
       $sql = "SELECT * from #__pixxis_groups WHERE id='$idORName'";
   }else{
       $sql = "SELECT * from #__pixxis_groups WHERE name='$idORName'";
   }

  $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
      PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
      die;
  }

  unset($g);
  $g = $this->_db->loadObject();

  $this->_ownerID = $g->ownerid;
  $this->_name = $g->name;
  $this->_id = $g->id;

  //now collect all user in groups from #__pixxis_usergroups
  $sql = "SELECT * FROM #__pixxis_usergroups WHERE groupid=". $this->_id;

  $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
        PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
        die;
  }

  $lst = $this->_db->loadObjectList();

  //now we have member id, we need name, from our table
  //collect a user in our group
  foreach($lst as $l)
  {
    $sql ="SELECT id, name, number FROM #__pixxis_phonebook WHERE id=$l->memberid";
    $this->_db->setQuery($sql);

    if( $this->_db->query() === false )
    {
        PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
        die;
    }

    $u = $this->_db->loadObject();
    $this->_members[$l->id] = $u;
    unset($u);
    //data structure
    //unique usergroups id --> object ( id, name, number ) phonebook
  }
}

function addMember($id)
{
  //given id, is the id from the phonebook entry !!!

  //check input
  if( !is_numeric( $id ) )
  {
       return false;
  }

  $my = &JFactory::getUser();
  //check if given id is a user phonebook entry
  $sql = "SELECT COUNT(*) AS COUNTER FROM #__pixxis_phonebook WHERE id=$id AND ownerid=". $my->get('id');

  $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
      PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
      die;
  }

  $u = $this->_db->loadObject();

  //given id is invalid
  if( $u->COUNTER <= 0 )
  {
     return false;
  }

  //insert new entry
  $sql = "INSERT INTO #__pixxis_usergroups VALUES(0, $id, $this->_id)";

  $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
	PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
    die;
  }

  return true;
}

function deleteMember( $id )
{

 //check input
  if( !is_numeric( $id ) )
  {
       return false;
  }

  $my = &JFactory::getUser();
  //check if given id is a user phonebook entry
  $sql = "SELECT COUNT(*) AS COUNTER FROM #__pixxis_phonebook WHERE id=$id AND ownerid=". $my->get('id');

  $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
      PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
      die;
  }

  $u = $this->_db->loadObject();

  //given id is invalid
  if( $u->COUNTER <= 0 )
  {
     return false;
  }

//insert new entry
  $sql = sprintf( "DELETE FROM #__pixxis_usergroups where memberid=%s and groupid=%s LIMIT 1", $id, $this->_id );
  
  $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
	PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
    die;
  }

  return true;  
  
}


function delete()
{
   //first delete all entries from #__pixxis_usergroups
   $sql ="DELETE FROM #__pixxis_usergroups WHERE groupid=$this->_id";

   $this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
        PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
        die;
  }

  //now delete group itself from #__pixxis_groups
  $sql ="DELETE FROM #__pixxis_groups WHERE id=$this->_id";

 	$this->_db->setQuery($sql);

  if( $this->_db->query() === false )
  {
        PixxisError::Alert( JText::_( 'PIXXIS_SQLQUERY_ERROR' ) );
        die;
  }
}

}
?>
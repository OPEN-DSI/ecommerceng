<?php
/*
 * @module		ECommerce
 * @version		1.0
 * @copyright	Auguria
 * @author		<franck.charpentier@auguria.net>
 * @licence		GNU General Public License
 */

dol_include_once('/ecommerce/class/data/eCommerceSociete.class.php');

class eCommerceSite // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='ecommerce_site';			//!< Id that identify managed objects
	//var $table_element='ecommerce_site';	//!< Name of table without prefix where object is stored
    
    var $id;
    
	var $name;
	var $type;
	var $webservice_address;
	var $user_name;
	var $user_password;
	var $filter_label;
	var $filter_value;
	var $fk_cat_societe;
	var $fk_cat_product;
	var $last_update;
	var $timeout;
	var $magento_use_special_price;
	var $magento_price_type;

	//The site type name is used to define class name in eCommerceRemoteAccess class
    private $siteTypes = array(1=>'magento');
	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function eCommerceSite($DB) 
    {
        $this->db = $DB;
        return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;
    	
		// Clean parameters
        
		if (isset($this->name)) $this->name=trim($this->name);
		if (isset($this->type)) $this->type=trim($this->type);
		if (isset($this->webservice_address)) $this->webservice_address=trim($this->webservice_address);
		if (isset($this->user_name)) $this->user_name=trim($this->user_name);
		if (isset($this->user_password)) $this->user_password=trim($this->user_password);
		if (isset($this->filter_label)) $this->filter_label=trim($this->filter_label);
		if (isset($this->filter_value)) $this->filter_value=trim($this->filter_value);
		if (isset($this->fk_cat_societe)) $this->fk_cat_societe=trim($this->fk_cat_societe);
		if (isset($this->fk_cat_product)) $this->fk_cat_product=trim($this->fk_cat_product);
		if (isset($this->timeout)) $this->timeout=trim($this->timeout);

        

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecommerce_site(";
		
		$sql.= "name,";
		$sql.= "type,";
		$sql.= "webservice_address,";
		$sql.= "user_name,";
		$sql.= "user_password,";
		$sql.= "filter_label,";
		$sql.= "filter_value,";
		$sql.= "fk_cat_societe,";
		$sql.= "fk_cat_product,";
		$sql.= "last_update,";
		$sql.= "timeout,";
		$sql.= "magento_use_special_price,";
		$sql.= "magento_price_type";

		
        $sql.= ") VALUES (";
        
		$sql.= " ".(! isset($this->name)?'NULL':"'".addslashes($this->name)."'").",";
		$sql.= " ".(! isset($this->type)?'NULL':"'".$this->type."'").",";
		$sql.= " ".(! isset($this->webservice_address)?'NULL':"'".addslashes($this->webservice_address)."'").",";
		$sql.= " ".(! isset($this->user_name)?'NULL':"'".addslashes($this->user_name)."'").",";
		$sql.= " ".(! isset($this->user_password)?'NULL':"'".addslashes($this->user_password)."'").",";
		$sql.= " ".(! isset($this->filter_label)?'NULL':"'".addslashes($this->filter_label)."'").",";
		$sql.= " ".(! isset($this->filter_value)?'NULL':"'".addslashes($this->filter_value)."'").",";
		$sql.= " ".(! isset($this->fk_cat_societe)?'NULL':"'".$this->fk_cat_societe."'").",";
		$sql.= " ".(! isset($this->fk_cat_product)?'NULL':"'".$this->fk_cat_product."'").",";
		$sql.= " ".(! isset($this->last_update) || strlen($this->last_update)==0?'NULL':"'".$this->db->idate($this->last_update)."'").",";
		$sql.= " ".(! isset($this->timeout)?'300':"'".intval($this->timeout)."'").",";
		$sql.= " ".(! isset($this->magento_use_special_price)?'0':"'".intval($this->magento_use_special_price)."'").",";
		$sql.= " ".(! isset($this->magento_price_type)?'HT':"'".$this->magento_price_type."'")."";

        
		$sql.= ")";

		$this->db->begin();
		
	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        
		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ecommerce_site");
            
            //create an entry for anonymous company
            $eCommerceSociete = new eCommerceSociete($this->db);
            $eCommerceSociete->fk_societe = dolibarr_get_const($this->db, 'ECOMMERCE_COMPANY_ANONYMOUS');
			$eCommerceSociete->fk_site = $this->id;
			$eCommerceSociete->remote_id = 0;
			if ($eCommerceSociete->create($user)<0)
			{
				$error++;
				$this->errors[]="Error ".$this->db->lasterror();
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }

    
    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.name,";
		$sql.= " t.type,";
		$sql.= " t.webservice_address,";
		$sql.= " t.user_name,";
		$sql.= " t.user_password,";
		$sql.= " t.filter_label,";
		$sql.= " t.filter_value,";
		$sql.= " t.fk_cat_societe,";
		$sql.= " t.fk_cat_product,";
		$sql.= " t.last_update,";
		$sql.= " t.timeout,";
		$sql.= " t.magento_use_special_price,";
		$sql.= " t.magento_price_type";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."ecommerce_site as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                
				$this->name = $obj->name;
				$this->type = $obj->type;
				$this->webservice_address = $obj->webservice_address;
				$this->user_name = $obj->user_name;
				$this->user_password = $obj->user_password;
				$this->filter_label = $obj->filter_label;
				$this->filter_value = $obj->filter_value;
				$this->fk_cat_societe = $obj->fk_cat_societe;
				$this->fk_cat_product = $obj->fk_cat_product;
				$this->last_update = $this->db->jdate($obj->last_update);
				$this->timeout = $obj->timeout;
				$this->magento_use_special_price = $obj->magento_use_special_price;
				$this->magento_price_type = $obj->magento_price_type;

                
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    

    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;
    	
		// Clean parameters
        
		if (isset($this->name)) $this->name=trim($this->name);
		if (isset($this->type)) $this->type=trim($this->type);
		if (isset($this->webservice_address)) $this->webservice_address=trim($this->webservice_address);
		if (isset($this->user_name)) $this->user_name=trim($this->user_name);
		if (isset($this->user_password)) $this->user_password=trim($this->user_password);
		if (isset($this->filter_label)) $this->filter_label=trim($this->filter_label);
		if (isset($this->filter_value)) $this->filter_value=trim($this->filter_value);
		if (isset($this->fk_cat_societe)) $this->fk_cat_societe=trim($this->fk_cat_societe);
		if (isset($this->fk_cat_product)) $this->fk_cat_product=trim($this->fk_cat_product);
		if (isset($this->timeout)) $this->timeout=trim($this->timeout);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."ecommerce_site SET";
        
		$sql.= " name=".(isset($this->name)?"'".addslashes($this->name)."'":"null").",";
		$sql.= " type=".(isset($this->type)?$this->type:"null").",";
		$sql.= " webservice_address=".(isset($this->webservice_address)?"'".addslashes($this->webservice_address)."'":"null").",";
		$sql.= " user_name=".(isset($this->user_name)?"'".addslashes($this->user_name)."'":"null").",";
		$sql.= " user_password=".(isset($this->user_password)?"'".addslashes($this->user_password)."'":"null").",";
		$sql.= " filter_label=".(isset($this->filter_label)?"'".addslashes($this->filter_label)."'":"null").",";
		$sql.= " filter_value=".(isset($this->filter_value)?"'".addslashes($this->filter_value)."'":"null").",";
		$sql.= " fk_cat_societe=".(isset($this->fk_cat_societe)?$this->fk_cat_societe:"null").",";
		$sql.= " fk_cat_product=".(isset($this->fk_cat_product)?$this->fk_cat_product:"null").",";
		$sql.= " last_update=".((isset($this->last_update) && $this->last_update != '') ? "'".$this->db->idate($this->last_update)."'" : 'null').",";
		$sql.= " timeout=".(isset($this->timeout)? "'".intval($this->timeout)."'" : '300').",";
		$sql.= " magento_use_special_price=".(isset($this->magento_use_special_price)? "'".intval($this->magento_use_special_price)."'" : '0').",";
        $sql.= " magento_price_type=".(isset($this->magento_price_type)? "'".$this->magento_price_type."'" : 'HT')."";
        
        $sql.= " WHERE rowid=".$this->id;
 
		$this->db->begin();
        
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        
		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.
				
	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}
		
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}		
    }
  
  
 	/**
	 *   \brief      Delete object in database
     *	\param      user        	User that delete
     *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *	\return		int				<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ecommerce_site";
		$sql.= " WHERE rowid=".$this->id;
	
		$this->db->begin();
		
		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		
		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action call a trigger.
				
		        //// Call triggers
		        //include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}	
		}
		
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}


	
	/**
	 *		\brief      Load an object from its id and create a new one in database
	 *		\param      fromid     		Id of object to clone
	 * 	 	\return		int				New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;
		
		$error=0;
		
		$object=new Ecommerce_site($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...
				
		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0) 
		{
			$this->error=$object->error;
			$error++;
		}
		
		if (! $error)
		{
			
			
			
		}
		
		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->name='';
		$this->type='';
		$this->webservice_address='';
		$this->user_name='';
		$this->user_password='';
		$this->filter_label='';
		$this->filter_value='';
		$this->fk_cat_societe='';
		$this->fk_cat_product='';
		$this->last_update='';
		$this->timeout='';	
		$this->magento_use_special_price='';
		$this->magento_price_type='';	
	}

	/**
	 *    \brief  	Renvoie la liste des sites
	 *    \return 	array		Tableau des id de site
	 */
	function listSites()
	{
		global $langs;
		$list = array();
		
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.name,";
		$sql.= " t.last_update";
        $sql.= " FROM ".MAIN_DB_PREFIX."ecommerce_site as t";
    
    	$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$list[$i] =array('id'=>$obj->rowid, 'name'=>$obj->name, 'last_update'=>$obj->last_update);
				$i++;
			}
		}
		return $list;
	}
	
	public function getSiteTypes()
	{
		return $this->siteTypes;
	}
}
?>

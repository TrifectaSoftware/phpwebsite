<?php

/**
 * Main class for the File Cabinet
 *
 * File Cabinet is meant (for those devs that utilize it)
 * as a central place to administrate all the files uploaded to the site.
 *
 * @version $Id$
 * @author Matthew McNaney <matt at tux dot appstate dot edu>
 */

PHPWS_Core::initModClass('filecabinet', 'Folder.php');
PHPWS_Core::requireConfig('filecabinet');

class Cabinet {
    var $title          = null;
    var $message        = null;
    var $content        = null;
    var $forms          = null;
    var $panel          = null;
    var $folder         = null;
    var $image_mgr      = null;
    var $document_mgr   = null;
    var $multimedia_mgr = null;
    var $file_manager   = null;


    /**
     * File manager administrative options.
     */
    function fmAdmin()
    {
        if (!$this->authenticate()) {
            Current_User::disallow();
        }

        Layout::cacheOff();
        if ($this->loadFileManager()) {
            Layout::nakedDisplay($this->file_manager->admin());
        } else {
            Layout::nakedDisplay(javascript('close_refresh'));
        }
    }

    /**
     * Document manager administrative options.
     */
    function dmAdmin()
    {
        if (!$this->authenticate()) {
            Current_User::disallow();
        }
        Layout::cacheOff();
        $this->loadDocumentManager();
        Layout::nakedDisplay($this->document_mgr->admin());
    }

    /**
     * Image manager administrative options.
     */
    function imAdmin()
    {
        if (!$this->authenticate()) {
            Current_User::disallow();
        }
        Layout::cacheOff();
        $this->loadImageManager();
        Layout::nakedDisplay($this->image_mgr->admin());
    }

    /**
     * Multimedia manager administrative options.
     */
    function mmAdmin()
    {
        if (!$this->authenticate()) {
            Current_User::disallow();
        }
        Layout::cacheOff();
        $this->loadMultimediaManager();
        Layout::nakedDisplay($this->multimedia_mgr->admin());
    }


    /**
     * Loads the file manager object into the cabinet variable.
     * Attempts to pull a current sessioned object if available
     */
    function loadFileManager()
    {
        PHPWS_Core::initModClass('filecabinet', 'File_Manager.php');

        if (!@$module = $_GET['cm']) {
            return false;
        }

        if (!@$itemname = $_GET['itn']) {
            return false;
        }

        $this->file_manager = new FC_File_Manager($module, $itemname, $_GET['fid']);
        if (isset($_GET['mw'])) {
            $this->file_manager->max_width = (int)$_GET['mw'];
        }

        if (isset($_GET['mh'])) {
            $this->file_manager->max_height = (int)$_GET['mh'];
        }

        if (isset($_GET['ftype'])) {
            $this->file_manager->folder_type = (int)$_GET['ftype'];
        }

        return true;
    }

    function admin()
    {
        $javascript = false; // if true, sends to nakedDisplay
        
        $this->loadPanel();

        if (isset($_REQUEST['aop'])) {
            $aop = $_REQUEST['aop'];
        } else {
            $aop = $this->panel->getCurrentTab();
        }

        if (!Current_User::isLogged()) {
            Current_User::disallow();
            return;
        }

        if ( ($aop != 'edit_image' && $aop != 'get_images') && !Current_User::allow('filecabinet') ){
            Current_User::disallow();
            return;
        }

        // Requires an unrestricted user
        switch ($aop) {
        case 'pin_folder':
        case 'delete_folder':
        case 'save_settings':
        case 'unpin':
        case 'settings':
            if (Current_User::isRestricted('filecabinet')) {
                Current_User::disallow();
            }
        }

        switch ($aop) {
            /** File manager functions **/
            /** end file manager functions **/

        case 'image':
            $this->panel->setCurrentTab('image');
            $this->title = dgettext('filecabinet', 'Image folders');
            $this->loadForms();
            $this->forms->getFolders(IMAGE_FOLDER);
            break;

        case 'multimedia':
            $this->panel->setCurrentTab('multimedia');
            $this->title = dgettext('filecabinet', 'Multimedia folders');
            $this->loadForms();
            $this->forms->getFolders(MULTIMEDIA_FOLDER);
            break;

        case 'add_folder':
            $javascript = true;
            $this->loadFolder();
            $this->addFolder();
            break;

        case 'pin_folder':
            if (!Current_User::authorized('filecabinet', 'edit_folders')) {
                Current_User::disallow();
            }

            $javascript = true;
            $this->pinFolder();
            javascript('close_refresh');
            break;

        case 'classify':
            $this->loadForms();
            $this->forms->classifyFileList();
            break;

        case 'classify_file':
            $this->loadForms();
            if (!empty($_POST['file_list'])) {
                $this->forms->classifyFile($_POST['file_list']);
            } elseif (isset($_GET['file'])) {
                $this->forms->classifyFile($_GET['file']);
            } else {
                $this->forms->classifyFileList();
            }
            break;

        case 'post_classifications':
            if (!Current_User::authorized('filecabinet')) {
                Current_User::disallow();
            }

            $result = $this->classifyFiles();
            if (is_array($result)) {
                $this->message = implode('<br />', $result);
            }
            $this->loadForms();
            $this->forms->classifyFileList();
            break;

        case 'unpin':
            if (!Current_User::authorized('filecabinet')) {
                Current_User::disallow();
            }

            Cabinet::unpinFolder();
            PHPWS_Core::goBack();
            break;

        case 'pin_form':
            $javascript = true;
            @$key_id = (int)$_GET['key_id'];
            if (!$key_id) {
                javascript('close_refresh', array('refresh'=>0));
                break;
            }

            $this->loadForms();
            $this->forms->pinFolder($key_id);
            break;

        case 'delete_folder':
            if (!Current_User::authorized('filecabinet', 'delete_folders', null, null, true)) {
                Current_User::disallow();
            }
            $this->loadFolder();
            $this->folder->delete();
            PHPWS_Core::goBack();
            break;

        case 'delete_incoming':
            $this->deleteIncoming();
            $this->loadForms();
            $this->forms->classifyFileList();
            break;

        case 'document':
            $this->panel->setCurrentTab('document');
            $this->title = dgettext('filecabinet', 'Document folders');
            $this->loadForms();
            $this->forms->getFolders(DOCUMENT_FOLDER);
            break;

        case 'edit_folder':
            $javascript = true;
            $this->loadFolder();
            // permission check in function below
            $this->editFolder();
            break;


        case 'change_tn':
            $javascript = true;
            $this->changeTN();
            break;

        case 'post_thumbnail':
            $javascript = true;
            if ($this->postTN()) {
                javascript('close_refresh');
            } else {
                $this->message = dgettext('filecabinet', 'Could not save thumbnail image.');
                $this->changeTN();
            }
            break;

        case 'post_folder':
            $this->loadFolder();
            if (!Current_User::authorized('filecabinet', 'edit_folders')) {
                Current_User::disallow();
            }

            if ($this->folder->post()) {
                if (!$this->folder->save()) {
                    Layout::nakedDisplay(dgettext('filecabinet', 'Failed to create folder. Please check your logs.'));
                } else {
                    Layout::nakedDisplay(javascript('close_refresh'));
                }
            } else {
                $this->message = $this->folder->_error;
                $this->addFolder();
            }
            break;

            
        case 'save_settings':
            $result = $this->saveSettings();
            if (is_array($result)) {
                $this->message = implode('<br />', $result);
            } else {
                $this->message = dgettext('filecabinet', 'Settings saved.');
            }
        case 'settings':
            $this->loadForms();
            $this->title = dgettext('filecabinet', 'Settings');
            $this->content = $this->forms->settings();
            break;

        case 'view_folder':
            $this->viewFolder();
            break;


        }

        $template['TITLE']   = &$this->title;
        $template['MESSAGE'] = &$this->message;
        $template['CONTENT'] = &$this->content;

        if ($javascript) {
            $main = PHPWS_Template::process($template, 'filecabinet', 'javascript.tpl');
            Layout::nakedDisplay($main);
        } else {
            $main = PHPWS_Template::process($template, 'filecabinet', 'main.tpl');
            $this->panel->setContent($main);
            $finalPanel = $this->panel->display();
            Layout::add(PHPWS_ControlPanel::display($finalPanel));
        }
    }

    function download($document_id)
    {
        require_once 'HTTP/Download.php';
        PHPWS_Core::initModClass('filecabinet', 'Document.php');

        $document = new PHPWS_Document($document_id);

        if (!empty($document->_errors)) {
            foreach ($this->_errors as $err) {
                PHPWS_Error::log($err);
            }
            Layout::add(dgettext('filecabinet', 'Sorry but this file is inaccessible at this time.'));
            return;
        }

        $folder = new Folder($document->folder_id);

        if (!$folder->allow()) {
            Layout::add(dgettext('filecabinet', 'Sorry, you are not allowed access to this file.'));
            return;
        }

        $file_path = $document->getPath();

        if (!is_file($file_path)) {
            PHPWS_Error::log(FC_DOCUMENT_NOT_FOUND, 'filecabinet', 'Cabinet_Action::download', $file_path);
            Layout::add(dgettext('filecabinet', 'Sorry but this file is inaccessible at this time.'));
            return;
        }

        $dl = new HTTP_Download;
        $dl->setFile($file_path);
        $dl->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, $document->filename);
        $dl->setContentType($document->file_type);
        $dl->send();
        exit();
    }

    function user($op=null)
    {
        if (empty($op)) {
            $op = & $_REQUEST['uop'];
        }

        switch($op) {
        case 'view_folder':
            $this->userViewFolder();
            break;
        }

        $template['TITLE']   = $this->title;
        $template['MESSAGE'] = $this->message;
        $template['CONTENT'] = $this->content;

        $main = PHPWS_Template::process($template, 'filecabinet', 'plain.tpl');
        Layout::add($main);
    }

    function fileManager($itemname, $file_id=0)
    {
        Layout::addStyle('filecabinet');
        PHPWS_Core::initModClass('filecabinet', 'File_Manager.php');
        $module = $_REQUEST['module'];
        if (!is_numeric($file_id)) {
            return false;
        }
        $manager = new FC_File_Manager($module, $itemname, $file_id);
        return $manager;
    }

    function viewImage($id)
    {
        Layout::addStyle('filecabinet');
        PHPWS_Core::initModClass('filecabinet', 'Image.php');
        $image = new PHPWS_Image($id);
        $tpl['TITLE'] = $image->title;
        $tpl['IMAGE'] = $image->getTag();
        $tpl['DESCRIPTION'] = $image->getDescription();
        $tpl['CLOSE'] = javascript('close_window');
        $content = PHPWS_Template::process($tpl, 'filecabinet', 'image_view.tpl');

        Layout::nakedDisplay($content);
    }

    function viewMultimedia($id)
    {
        Layout::addStyle('filecabinet');
        PHPWS_Core::initModClass('filecabinet', 'Multimedia.php');
        $multimedia = new PHPWS_Multimedia($id);
        $tpl['TITLE'] = $multimedia->title;
        $tpl['MULTIMEDIA'] = $multimedia->getTag();
        $tpl['DESCRIPTION'] = $multimedia->getDescription();
        $tpl['CLOSE'] = javascript('close_window');
        $content = PHPWS_Template::process($tpl, 'filecabinet', 'multimedia_view.tpl');

        Layout::nakedDisplay($content);
    }

    function addFolder()
    {
        $this->loadForms();
        if ($this->folder->ftype == IMAGE_FOLDER) {
            $this->title   = dgettext('filecabinet', 'Create image folder');
        } elseif ($this->folder->ftype == DOCUMENT_FOLDER) {
            $this->title   = dgettext('filecabinet', 'Create document folder');
        } else {
            $this->title   = dgettext('filecabinet', 'Create multimedia folder');
        }
        $this->content = $this->forms->editFolder($this->folder);
    }

    function editFolder()
    {
        if (!Current_User::allow('filecabinet', 'edit_folders', $this->folder->id, 'folder')) {
            Current_User::disallow();
        }

        $this->loadForms();
        if ($this->folder->ftype == IMAGE_FOLDER) {
            $this->title   = dgettext('filecabinet', 'Update image folder');
        } elseif ($this->folder->ftype == DOCUMENT_FOLDER) {
            $this->title   = dgettext('filecabinet', 'Update document folder');
        } else {
            $this->title   = dgettext('filecabinet', 'Update multimedia folder');
        }
        $this->content = $this->forms->editFolder($this->folder);
    }
    

    function loadImageManager()
    {
        PHPWS_Core::initModClass('filecabinet', 'Image_Manager.php');
        $this->image_mgr = new FC_Image_Manager;
    }

    function loadDocumentManager()
    {
        PHPWS_Core::initModClass('filecabinet', 'Document_Manager.php');
        $this->document_mgr = new FC_Document_Manager;
    }

    function loadMultimediaManager()
    {
        PHPWS_Core::initModClass('filecabinet', 'Multimedia_Manager.php');
        $this->loadFolder(MULTIMEDIA_FOLDER);
        $this->multimedia_mgr = new FC_Multimedia_Manager;
    }


    function loadForms()
    {
        PHPWS_Core::initModClass('filecabinet', 'Forms.php');
        $this->forms = new Cabinet_Form;
        $this->forms->cabinet = & $this;
    }

    function unpinFolder()
    {
        if (!isset($_REQUEST['folder_id']) || !isset($_REQUEST['key_id'])) {
            return;
        }

        $folder_id = (int)$_REQUEST['folder_id'];
        $key_id    = (int)$_REQUEST['key_id'];

        $db = new PHPWS_DB('filecabinet_pins');
        $db->addWhere('folder_id', $folder_id);
        $db->addWhere('key_id', $key_id);
        $db->delete();
    }

    function pinFolder()
    {
        if (!isset($_POST['folder_id']) || !isset($_POST['key_id'])) {
            return;
        }

        $folder_id = (int)$_POST['folder_id'];
        $key_id = (int)$_POST['key_id'];

        $db = new PHPWS_DB('filecabinet_pins');
        $db->addWhere('folder_id', $folder_id);
        $db->addWhere('key_id', $key_id);
        $db->delete();

        $db->addValue('folder_id', $folder_id);
        $db->addValue('key_id', $key_id);
        $result = $db->insert();
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
        }
    }

    function passImages()
    {
        header("Content-type: text/plain");
        $this->loadFolder();
        $this->loadImageManager();
        echo $this->image_mgr->showImages($this->folder);
        exit();
    }


    function loadPanel()
    {
        PHPWS_Core::initModClass('controlpanel', 'Panel.php');
        $link = 'index.php?module=filecabinet';

        $image_command      = array('title'=>dgettext('filecabinet', 'Image folders'), 'link'=> $link);
        $document_command   = array('title'=>dgettext('filecabinet', 'Document folders'), 'link'=> $link);
        $multimedia_command = array('title'=>dgettext('filecabinet', 'Multimedia folders'), 'link'=> $link);
        $classify_command   = array('title'=>dgettext('filecabinet', 'Classify'), 'link'=> $link);

        $tabs['image']      = $image_command;
        $tabs['document']   = $document_command;
        $tabs['multimedia'] = $multimedia_command;
        $tabs['classify']   = $classify_command;

        if (Current_User::isUnrestricted('filecabinet')) {
            $tabs['settings']  = array('title'=> dgettext('filecabinet', 'Settings'), 'link' => $link);
        }

        $this->panel = new PHPWS_Panel('filecabinet');
        $this->panel->quickSetTabs($tabs);
        $this->panel->setModule('filecabinet');
    }

    function saveSettings()
    {
        if (empty($_POST['base_doc_directory'])) {
            $errors[] = dgettext('filecabinet', 'Default document directory may not be blank');
        } elseif (!is_dir($_POST['base_doc_directory'])) {
            $errors[] = dgettext('filecabinet', 'Document directory does not exist.');
        } elseif (!is_writable($_POST['base_doc_directory'])) {
            $errors[] = dgettext('filecabinet', 'Unable to write to document directory.');
        } elseif (!is_readable($_POST['base_doc_directory'])) {
            $errors[] = dgettext('filecabinet', 'Unable to read document directory.');
        } else {
            $dir = $_POST['base_doc_directory'];
            if (!preg_match('@/$@', $dir)) {
                $dir .= '/';
            }
            PHPWS_Settings::set('filecabinet', 'base_doc_directory', $dir);
        }

        if (empty($_POST['max_image_dimension']) || $_POST['max_image_dimension'] < 50) {
            $errors[] = dgettext('filecabinet', 'The max image dimension must be greater than 50 pixels.');
        } else {
            PHPWS_Settings::set('filecabinet', 'max_image_dimension', $_POST['max_image_dimension']);
        }


        $max_file_upload = preg_replace('/\D/', '', ini_get('upload_max_filesize'));

        if (empty($_POST['max_image_size'])) {
            $errors[] = dgettext('filecabinet', 'You must set a maximum image file size.');
        } else {
            $max_image_size = (int)$_POST['max_image_size'];
            if ( ($max_image_size / 1000000) > ((int)$max_file_upload) ) {
                $errors[] = sprintf(dgettext('filecabinet', 'Your maximum image size exceeds the server limit of %sMB.'), $max_file_upload);
            } else {
                PHPWS_Settings::set('filecabinet', 'max_image_size', $max_image_size);
            }
        }

        if (empty($_POST['max_document_size'])) {
            $errors[] = dgettext('filecabinet', 'You must set a maximum document file size.');
        } else {
            $max_document_size = (int)$_POST['max_document_size'];
            if ( ($max_document_size / 1000000) > (int)$max_file_upload ) {
                $errors[] = sprintf(dgettext('filecabinet', 'Your maximum document size exceeds the server limit of %sMB.'), $max_file_upload);
            } else {
                PHPWS_Settings::set('filecabinet', 'max_document_size', $max_document_size);
            }
        }

        if (empty($_POST['max_multimedia_size'])) {
            $errors[] = dgettext('filecabinet', 'You must set a maximum multimedia file size.');
        } else {
            $max_multimedia_size = (int)$_POST['max_multimedia_size'];
            if ( ($max_multimedia_size / 1000000) > (int)$max_file_upload ) {
                $errors[] = sprintf(dgettext('filecabinet', 'Your maximum multimedia size exceeds the server limit of %sMB.'), $max_file_upload);
            } else {
                PHPWS_Settings::set('filecabinet', 'max_multimedia_size', $max_multimedia_size);
            }
        }

        if (empty($_POST['max_pinned_images'])) {
            PHPWS_Settings::set('filecabinet', 'max_pinned_images', 0);
        } else {
            PHPWS_Settings::set('filecabinet', 'max_pinned_images', (int)$_POST['max_pinned_images']);
        }

        $threshold = (int)$_POST['crop_threshold'];
        if ($threshold < 0) {
            PHPWS_Settings::set('filecabinet', 'crop_threshold', 0);
        } else {
            PHPWS_Settings::set('filecabinet', 'crop_threshold', $threshold);
        }

        if (empty($_POST['max_pinned_documents'])) {
            PHPWS_Settings::set('filecabinet', 'max_pinned_documents', 0);
        } else {
            PHPWS_Settings::set('filecabinet', 'max_pinned_documents', (int)$_POST['max_pinned_documents']);
        }

        if (isset($_POST['use_ffmpeg'])) {
            PHPWS_Settings::set('filecabinet', 'use_ffmpeg', 1);
        } else {
            PHPWS_Settings::set('filecabinet', 'use_ffmpeg', 0);
        }

        if (isset($_POST['auto_link_parent'])) {
            PHPWS_Settings::set('filecabinet', 'auto_link_parent', 1);
        } else {
            PHPWS_Settings::set('filecabinet', 'auto_link_parent', 0);
        }

        if (isset($_POST['caption_images'])) {
            PHPWS_Settings::set('filecabinet', 'caption_images', 1);
        } else {
            PHPWS_Settings::set('filecabinet', 'caption_images', 0);
        }

        $ffmpeg_dir = strip_tags($_POST['ffmpeg_directory']);
        if (empty($ffmpeg_dir)) {
            PHPWS_Settings::set('filecabinet', 'ffmpeg_directory', null);
            PHPWS_Settings::set('filecabinet', 'use_ffmpeg', 0);
        } else {
            if (!preg_match('@/$@', $ffmpeg_dir)) {
                $ffmpeg_dir .= '/';
            }
            PHPWS_Settings::set('filecabinet', 'ffmpeg_directory', $ffmpeg_dir);
            if (!is_file($ffmpeg_dir . 'ffmpeg')) {
                $errors[] = dgettext('filecabinet', 'Could not find ffmpeg executable.');
                PHPWS_Settings::set('filecabinet', 'use_ffmpeg', 0);
            }
        }

        if (FC_ALLOW_CLASSIFY_DIR_SETTING) {
            if (!empty($_POST['classify_directory'])) {
                $classify_dir = $_POST['classify_directory'];
                if (!preg_match('@/$@', $classify_dir)) {
                    $classify_dir .= '/';
                }
                if (!is_dir($classify_dir)) {
                    $errors[] = dgettext('filecabinet', 'Classify directory could not be found.');
                } elseif(!is_writable($classify_dir)) {
                    $errors[] = dgettext('filecabinet', 'The web server does not have permissions for the classify directory.');
                } else {
                    PHPWS_Settings::set('filecabinet', 'classify_directory', $classify_dir);
                }
            }
        }

        PHPWS_Settings::save('filecabinet');
        if (isset($errors)) {
            return $errors;
        } else {
            return true;
        }
    }

    function userViewFolder()
    {
        $this->loadFolder();
        if (!$this->folder->id || !$this->folder->public_folder) {
            $this->title = dgettext('filecabinet', 'Sorry');
            $this->content = dgettext('filecabinet', 'This is a private folder.');
            return;
        }
        if (!$this->folder->allow()) {
            if (Current_User::isLogged()) {
                $this->title = dgettext('filecabinet', 'Sorry');
                $this->content = dgettext('filecabinet', 'You do not have permission to view this folder.');
            } else {
                Current_User::requireLogin();
            }
            return;
        }
        $this->title = $this->folder->title;
        $this->loadForms();
        $kids = PHPWS_Settings::get('filecabinet', 'no_kids');
        $this->forms->folderContents($this->folder, false, $kids);
    }

    function viewFolder()
    {
        $this->loadFolder();
        if (!$this->folder->id) {
            PHPWS_Core::errorPage('404');
        }

        $this->title = sprintf('%s - %s', $this->folder->title, $this->folder->getPublic());
        $this->loadForms();
        $this->forms->folderContents($this->folder);
    }

    function fileInfo($file)
    {
        static $images = null;
        static $mm     = null;
        static $docs   = null;

        PHPWS_Core::requireConfig('core', 'file_types.php');

        if (empty($images)) {
            $images = unserialize(ALLOWED_IMAGE_TYPES);
        }

        if (empty($mm)) {
            $mm     = unserialize(ALLOWED_MULTIMEDIA_TYPES);
        }

        if (empty($docs)) {
            $docs   = unserialize(ALLOWED_DOCUMENT_TYPES);
        }

        if (!is_file($file)) {
            return null;
        }

        $file_type = mime_content_type($file);
        
        // some files are not correctly identified
        if ($file_type == 'text/plain') {
            $ext = PHPWS_File::getFileExtension($file);
            if ($ext != 'txt') {
                if (isset($images[$ext])) {
                    $file_type = $images[$ext];
                } elseif (isset($mm[$ext])) {
                    $file_type = $mm[$ext];
                } elseif (isset($docs[$ext])) {
                    $file_type = $docs[$ext];
                }
            }
        }

        if (in_array($file_type, $images)) {
            $info['image'] = true;
        } else {
            $info['image'] = false;
        }

        if (in_array($file_type, $docs)) {
            $info['document'] = true;
        } else {
            $info['document'] = false;
        }

        if (in_array($file_type, $mm)) {
            $info['multimedia'] = true;
        } else {
            $info['multimedia'] = false;
        }

        $info['file_type'] = $file_type;

        return $info;
    }

    /**
     * Saves files posted in the forms classifyFileList function
     */
    function classifyFiles()
    {
        PHPWS_Core::initModClass('filecabinet', 'Image.php');
        PHPWS_Core::initModClass('filecabinet', 'Document.php');
        PHPWS_Core::initModClass('filecabinet', 'Multimedia.php');

        if (empty($_POST['file_count'])) {
            return false;
        }

        foreach ($_POST['file_count'] as $key=>$filename) {
            $folder_id = $_POST['folder'][$key];
            $folder = new Folder($folder_id);

            if (empty($_POST['file_title'][$key])) {
                $error[$filename] = dgettext('filecabinet', 'Missing title.');
            }

            // initialize a new file object
            switch ($folder->ftype) {
            case IMAGE_FOLDER:
                $file_obj = new PHPWS_Image;
                break;

            case DOCUMENT_FOLDER:
                $file_obj = new PHPWS_Document;
                break;

            case MULTIMEDIA_FOLDER:
                $file_obj = new PHPWS_Multimedia;
                break;
            }

            // save the folder id and basic information

            $file_obj->folder_id = $folder->id;
            $file_obj->file_name = $filename;

            $file_obj->setTitle($_POST['file_title'][$key]);
            $file_obj->setDirectory($folder->getFullDirectory());

            if (!empty($_POST['file_description'][$key])) {
                $file_obj->setDescription($_POST['file_description'][$key]);
            }

            // move the file from the incoming directory
            $classify_dir = $this->getClassifyDir();
            if (empty($classify_dir)) {
                return array(dgettext('filecabinet', 'The web server does not have permission to access files in the classify directory.'));
            }
            $incoming_file = $classify_dir . $filename;
            $folder_directory = $file_obj->getPath();


            if (!@rename($incoming_file, $folder_directory)) {
                $errors[$filename] = sprintf(dgettext('filecabinet', 'Could not move file "%s" to "%s" folder directory.'), $filename, $folder->title);
                PHPWS_Error::log(FC_FILE_MOVE, 'filecabinet', 'Cabinet::classifyFiles', $folder_directory);
                continue;
            }

            $file_info = $this->fileInfo($file_obj->getPath());
            $file_obj->file_type = $file_info['file_type'];
            $file_obj->loadFileSize();

            // if image is getting saved, need to process
            if ($folder->ftype == IMAGE_FOLDER) {
                $file_obj->loadDimensions();
                $file_obj->save(true, false);
            } else {
                $file_obj->save(false);
            }
        }

        if (isset($errors)) {
            return $errors;
        } else {
            return true;
        }
    }

    function deleteIncoming()
    {
        if (empty($_POST['file_list'])) {
            if (isset($_GET['file'])) {
                $file_list[] = $_GET['file'];
            } else {
                return;
            }
        } else {
            $file_list = & $_POST['file_list'];
        }

        $classify_dir = $this->getClassifyDir();

        if (empty($classify_dir)) {
            $this->message = dgettext('filecabinet', 'The web server does not have permission to delete files from the classify directory.');
        }

        if (!is_array($file_list)) {
            return;
        }

        foreach ($file_list as $filename) {
            $file = $classify_dir . $filename;
            @unlink($classify_dir . $filename);
        }
    }

    function getMaxSizes()
    {
        $sys_size = str_replace('M', '', ini_get('upload_max_filesize'));
        $sys_size = $sys_size * 1000000;
        $form = new PHPWS_Form;

        $sizes['system']     = & $sys_size;
        $sizes['form']       = & $form->max_file_size;
        $sizes['document']   = PHPWS_Settings::get('filecabinet', 'max_document_size');
        $sizes['image']      = PHPWS_Settings::get('filecabinet', 'max_image_size');
        $sizes['multimedia'] = PHPWS_Settings::get('filecabinet', 'max_multimedia_size');
        $sizes['absolute']   = ABSOLUTE_UPLOAD_LIMIT;

        return $sizes;
    }

    function getClassifyDir()
    {
        if (FC_ALLOW_CLASSIFY_DIR_SETTING) {
            $directory = PHPWS_Settings::get('filecabinet', 'classify_directory');
        } else {
            $directory = FC_CLASSIFY_DIRECTORY;
        }

        if (is_writable($directory)) {
            return $directory;
        } else {
            return null;
        }
    }

    function changeTN()
    {
        $form = new PHPWS_Form('thumbnail');
        $form->addHidden('module', 'filecabinet');
        $form->addHidden('aop', 'post_thumbnail');
        $form->addHidden('type', $_REQUEST['type']);
        $form->addHidden('id', $_REQUEST['id']);
        $form->addFile('thumbnail');
        $form->setLabel('thumbnail', dgettext('filecabinet', 'Upload thumbnail'));
        $form->addSubmit(dgettext('filecabinet', 'Upload'));

        if ($_REQUEST['type'] == 'mm') {
            PHPWS_Core::initModClass('filecabinet', 'Multimedia.php');
            $mm = new PHPWS_Multimedia($_REQUEST['id']);
            if (!$mm->id) {
                return false;
            }
        }

        $tpl = $form->getTemplate();

        $tpl['CLOSE'] = javascript('close_window');

        $warnings[] = sprintf(dgettext('filecabinet', 'Max thumbnail size : %sx%s.'), FC_THUMBNAIL_WIDTH, FC_THUMBNAIL_HEIGHT);
        if ($mm->isVideo()) {
            $warnings[] = dgettext('filecabinet', 'Image must be a jpeg file.');
        }

        $tpl['WARNINGS'] = implode('<br />', $warnings);
        $this->title = dgettext('filecabinet', 'Upload new thumbnail');

        $this->content = PHPWS_Template::process($tpl, 'filecabinet', 'thumbnail.tpl');
    }

    function postTN()
    {
        PHPWS_Core::initModClass('filecabinet', 'Image.php');

        if ($_POST['type'] == 'mm') {
            PHPWS_Core::initModClass('filecabinet', 'Multimedia.php');
            $obj = new PHPWS_Multimedia($_POST['id']);
            if (!$obj->id) {
                return false;
            }
        }

        $image = new PHPWS_Image;
        $image->setMaxWidth(FC_THUMBNAIL_WIDTH);
        $image->setMaxHeight(FC_THUMBNAIL_HEIGHT);
        if (!$image->importPost('thumbnail')) {
            return false;
        }

        if ($obj->isVideo() && $image->file_type != 'image/jpeg' && $image->file_type != 'image/jpg') {
            return false;
        }

        $image->file_directory = $obj->thumbnailDirectory();
        $image->file_name = $obj->dropExtension() . '.' . $image->getExtension();
        $image->write();

        if ($obj->_classtype == 'multimedia') {
            $obj->thumbnail = & $image->file_name;
            $obj->save(false, false);
        }
        return true;
    }

    function listFolders($type=null, $simple=false)
    {
        $db = new PHPWS_DB('folders');
        if ($type) {
            $db->addWhere('ftype', (int)$type);
        }
        if ($simple) {
            $db->select();
        }
    }


    function getFile($id, $style=true)
    {
        if ($style) {
            Layout::addStyle('filecabinet', 'file_view.css');
        }
        PHPWS_Core::initModClass('filecabinet', 'File_Assoc.php');
        $file_assoc = new FC_File_Assoc($id);
        return $file_assoc->getTag();
    }

    function loadFolder($folder_id=0)
    {
        if (!$folder_id && isset($_REQUEST['folder_id'])) {
            $folder_id = &$_REQUEST['folder_id'];
        }

        $this->folder = new Folder($folder_id);
        if (!$this->folder->id) {
            $this->folder->ftype = $_REQUEST['ftype'];
        }
    }

    function authenticate()
    {
        if (!Current_User::isLogged()) {
            javascript('close_refresh');
            Layout::nakedDisplay();
            exit();
        }

        $module = @$_REQUEST['module'];

        if (!$module) {
            return false;
        }

        return Current_User::allow($module);
    }

    function convertImagesToFileAssoc($table, $column)
    {
        $db = new PHPWS_DB($table);
        $db->addColumn('id');
        $db->addColumn($column);
        $db->setIndexBy('id');
        $images = $db->select('col');
        if (empty($images)) {
            return true;
        }

        foreach ($items as $item_id=>$image_id) {
            $db->reset();

            if (@$file_assoc_id = $images_converted[$image_id]) {
                $db->addValue($column, $file_assoc_id);
                $db->addWhere('id', $item_id);
                PHPWS_Error::logIfError($db->update());
            } else {
                $file_assoc = new FC_File_Assoc;
                $file_assoc->file_type = FC_IMAGE;
                $file_assoc->file_id = $image_id;
                if (!PHPWS_Error::logIfError($file_assoc->save())) {
                    $db->addValue($column, $file_assoc->id);
                    $db->addWhere('id', $item_id);
                    if (PHPWS_Error::logIfError($db->update())) {
                        continue;
                    }
                }
                $images_converted[$image_id] = $file_assoc->id;
            }
        }
        return true;
    }
}

?>
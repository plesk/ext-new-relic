<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class IndexController extends pm_Controller_Action
{
    protected $_accessLevel = 'admin';

    public function init()
    {
        parent::init();

        // Init title for all actions
        $this->view->pageTitle = $this->lmsg('page_title');
    }

    public function indexAction()
    {
        // Default action is formAction
        $this->_forward('form');
    }

    public function formAction()
    {
        // Set the description text
        $this->view->output_description = $this->lmsg('page_title_description');
        $this->view->output_createaccount = $this->lmsg('create_account_first');

        // Init form here
        $form = new pm_Form_Simple();
        $form->addElement('text', 'license_key', ['label' => $this->lmsg('form_license_key'), 'value' => pm_Settings::get('license_key'), 'required' => true, 'validators' => [['NotEmpty', true],],]);
        $form->addElement('text', 'server_name', ['label' => $this->lmsg('form_server_name'), 'value' => pm_Settings::get('server_name'), 'required' => true, 'validators' => [['NotEmpty', true],],]);
        $form->addElement('description', 'server_types', ['description' => $this->lmsg('form_product_types'), 'escape' => false]);
        $this->installationType('servers', $form);
        $this->installationType('apm', $form);
        $form->addControlButtons(['cancelLink' => pm_Context::getModulesListUrl(),]);

        // Process the form - save the license key and run the installation scripts
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $license_key = $form->getValue('license_key');

            if (!empty($license_key)) {
                pm_Settings::set('license_key', $license_key);

                $server_name = $form->getValue('server_name');
                pm_Settings::set('server_name', $server_name);

                if ($form->getValue('servers')) {
                    if ($this->runInstallation('servers', $license_key, $server_name)) {
                        pm_Settings::set('servers', $form->getValue('servers'));
                    }
                }

                if ($form->getValue('apm')) {
                    if ($this->runInstallation('apm', $license_key, $server_name)) {
                        pm_Settings::set('apm', $form->getValue('apm'));
                    }
                }

                $this->_status->addMessage('info', $this->lmsg('message_success'));
                $this->_helper->json(['redirect' => pm_Context::getBaseUrl()]);
            }
        }

        $this->view->form = $form;
    }

    private function runInstallation($type, $license_key, $server_name = '')
    {
        $options = array();

        $options[] = $license_key;
        $options[] = addslashes($server_name);

        $result = pm_ApiCli::callSbin($type.'.sh', $options, pm_ApiCli::RESULT_FULL);

        if ($result['code']) {
            throw new pm_Exception("{$result['stdout']}\n{$result['stderr']}");
        }

        return true;
    }

    private function installationType($type, &$form)
    {
        $installation_done = $this->checkInstallationState($type);

        if (empty($installation_done)) {
            if ($type == 'servers') {
                $form->addElement('checkbox', 'servers', ['label' => $this->lmsg('form_type_servers'), 'value' => pm_Settings::get('servers')]);
                $form->addElement('description', 'type_servers_description', ['description' => $this->lmsg('form_type_servers_description'), 'escape' => false]);
            } elseif ($type == 'apm') {
                $form->addElement('checkbox', 'apm', ['label' => $this->lmsg('form_type_apm'), 'value' => pm_Settings::get('apm')]);
                $form->addElement('description', 'type_apm_description', ['description' => $this->lmsg('form_type_apm_description'), 'escape' => false]);
            }

            return;
        }

        if ($type == 'servers') {
            $form->addElement('description', 'servers_installed', ['description' => $this->lmsg('form_type_servers_installed'), 'escape' => false]);
            $form->addElement('checkbox', 'servers', ['label' => $this->lmsg('form_type_servers_reinstall'), 'value' => '']);
            $form->addElement('description', 'type_servers_description', ['description' => $this->lmsg('form_type_servers_description'), 'escape' => false]);
        } elseif ($type == 'apm') {
            $form->addElement('description', 'apm_installed', ['description' => $this->lmsg('form_type_apm_installed'), 'escape' => false]);
            $form->addElement('checkbox', 'apm', ['label' => $this->lmsg('form_type_apm_reinstall'), 'value' => '']);
            $form->addElement('description', 'type_apm_description', ['description' => $this->lmsg('form_type_apm_description'), 'escape' => false]);
        }
    }

    private function checkInstallationState($type)
    {
        return pm_Settings::get($type);
    }
}

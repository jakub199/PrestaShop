<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */


namespace PrestaShopBundle\Controller\Admin\AdvancedParameters;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;

class SystemInformationController extends FrameworkBundleAdminController
{
    const CONTROLLER_NAME = 'SystemInformation';

    public function indexAction(Request $request)
    {
        $summary = $this->getRequirementsChecker()->getSummary();

        $twigValues = array(
            'layoutHeaderToolbarBtn' => [],
            'layoutTitle' => $this->get('translator')->trans('Information', array(), 'Admin.Navigation.Menu'),
            'requireAddonsSearch' => true,
            'requireBulkActions' => false,
            'showContentHeader' => true,
            'enableSidebar' => true,
            'help_link' => '',
            'requireFilterStatus' => false,
            'level' => $this->authorizationLevel($this::CONTROLLER_NAME),
            'errorMessage' => 'ok',
            'hostMode' => $this->getHosting()->isHostMode(),
            'server' => $this->getHosting()->getServerInformation(),
            'instaWebInstalled' => $this->getHosting()->isApacheInstawebModule(),
            'database' => $this->getHosting()->getDatabaseInformation(),
            'shop' => $this->getShop()->getShopInformation(),
            'isNativePHPmail' => $this->getMailing()->isNativeMailUsed(),
            'smtp' => $this->getMailing()->getSmtpInformation(),
            'userAgent' => $request->headers->get('User-Agent'),
        );


        return $this->render('PrestaShopBundle:Admin/AdvancedParameters:system_information.html.twig', array_merge($twigValues, $summary));
    }

    public function displayAjaxCheckFilesAction()
    {
        $xml = @simplexml_load_file(_PS_API_URL_.'/xml/md5/'._PS_VERSION_.'.xml');
        if (!$xml) {
            die(json_encode($this->file_list));
        }

        $this->getListOfUpdatedFiles($xml->ps_root_dir[0]);
        die(json_encode($this->file_list));
    }

    public function getListOfUpdatedFiles(SimpleXMLElement $dir, $path = '')
    {
        $exclude_regexp = '(install(-dev|-new)?|themes|tools|cache|docs|download|img|localization|log|mails|translations|upload|modules|override/(:?.*)index.php$)';
        $admin_dir = basename(_PS_ADMIN_DIR_);

        foreach ($dir->md5file as $file) {
            $filename = preg_replace('#^admin/#', $admin_dir.'/', $path.$file['name']);
            if (preg_match('#^'.$exclude_regexp.'#', $filename)) {
                continue;
            }

            if (!file_exists(_PS_ROOT_DIR_.'/'.$filename)) {
                $this->file_list['missing'][] = $filename;
            } else {
                $md5_local = md5_file(_PS_ROOT_DIR_.'/'.$filename);
                if ($md5_local != (string)$file) {
                    $this->file_list['updated'][] = $filename;
                }
            }
        }

        foreach ($dir->dir as $subdir) {
            $this->getListOfUpdatedFiles($subdir, $path.$subdir['name'].'/');
        }
    }

    private function getHosting()
    {
        return $this->get('prestashop.adapter.hosting_information');
    }

    private function getMailing()
    {
        return $this->get('prestashop.adapter.mailing_information');
    }

    private function getShop()
    {
        return $this->get('prestashop.adapter.shop_information');
    }

    private function getRequirementsChecker()
    {
        return $this->get('prestashop.adapter.check_requirements');
    }
}

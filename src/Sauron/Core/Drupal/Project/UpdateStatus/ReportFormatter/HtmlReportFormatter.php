<?php

namespace Sauron\Core\Drupal\Project\UpdateStatus\ReportFormatter;

use Sauron\Core\Drupal\Project\Entity\Module;
use Sauron\Core\Drupal\Project\Entity\Project;

/**
 * Render an update status report in HTML
 *
 * @author Alan Moreau <moreau.alan@gmail.com>
 */
class HtmlReportFormatter
{
    /**
     * @var array column's names
     */
    private static $headers = array('Module', 'Installed version', 'Last security update version', 'Last bug fix version');

    /**
     * @var string styles use to colorize line according to the extension status
     */
    CONST UNSUPPORTED_STYLE = '#EDEDED';
    CONST INFO_STYLE        = '#DDFFDD';
    CONST BUG_STYLE         = '#FFFFDD';
    CONST SECURITY_STYLE    = '#FFCCCC';
    CONST INFO_LINK_STYLE   = 'font-weight: normal;font-style: normal;color: #4d4d4d;font-size: 12px;';
    CONST UNSUPPORTED_LINK_STYLE   = 'font-weight: normal;font-style: normal;font-size: 12px;';
    CONST BUG_LINK_STYLE    = 'font-weight: normal;font-style: normal;color: #58ACFA;';
    CONST SECURITY_LINK_STYLE = 'font-weight: normal;font-style: normal;font-size: 14px;color:#FA8258';
    CONST UNSUPPORTED_FORMAT = '<td style="border : 1px solid black" bgcolor="#%color"><a style="%link_style">%label</a></td>';
    CONST MODULE_FORMAT     = '<td style="border : 1px solid black" bgcolor="#%color"><a href="https://www.drupal.org/project/%project" style="%link_style">%label</a></td>';
    CONST VERSION_FORMAT    = '<td style="border : 1px solid black" bgcolor="#%color"><a href="https://www.drupal.org/project/%project/releases/%version" style="%link_style">%label</a></td>';

    /**
     * Render the report
     *
     * @param OutputInterface $output
     * @param Project $project the project related to update status
     * @param $updateStatus the update status
     */
    public function render(Project $project, $updateStatus)
    {

        $coreTable  = '<h1>This is the update status report of your project ' . $project->name . '</h1>';
        $coreTable .= '<table style="border-collapse: collapse; border : 1px solid black">';
        $coreTable .= '<tr style="border : 1px solid black">' . $this->getHeader() . '</tr>';

        //Drupal core
        if (isset($updateStatus['drupal'])) {
            $coreTable .= $this->getRow($updateStatus['drupal']);
        }

        $coreTable .= '</table>';

        $modulesTable  = '<table style=" border-collapse: collapse; border : 1px solid black">';
        $modulesTable .= '<tr style="border : 1px solid black">' . $this->getHeader() . '</tr>';

        foreach($project->getModules() as $module) {
            if (isset($updateStatus['modules'][$module->machineName])) {
                $updateStatusEntry = $updateStatus['modules'][$module->machineName];
                $modulesTable .= $this->getRow($updateStatusEntry, $module);
            }
        }

        $modulesTable .= '</table>';

        return $coreTable . '<br>' . $modulesTable;
    }

    /**
     * Return HTML table header
     *
     * @return string
     */
    protected function getHeader()
    {
        $headers = self::$headers;
        array_walk($headers, function(&$value) {
            $value = '<th style="border : 1px solid black">' . $value . '</th>';
        });
        return implode('', $headers);
    }

    /**
     * Retrieves table row according to extension update status
     *
     * @param $updateStatusEntry
     * @param Module $module
     * @return array
     */
    protected function getRow($updateStatusEntry, Module $module = NULL)
    {
        $moduleName                = '';
        $installedVersion          = $updateStatusEntry['current_version'];
        $lastBugFixVersion         = $updateStatusEntry['last_bug_fix_version'];
        $lastSecurityUpdateVersion = $updateStatusEntry['last_security_fix_version'];

        if ($module === NULL) {
            $moduleName = 'Drupal';
            $machineName = 'drupal';
        }
        else {
            $moduleName = $module->name;
            $machineName = $module->machineName;
        }

        $style = self::INFO_STYLE;
        $format = self::MODULE_FORMAT;
        $linkStyle = self::INFO_LINK_STYLE;

        if ($updateStatusEntry['current_rank'] == 0) {
            $style = self::UNSUPPORTED_STYLE;
            $format = self::UNSUPPORTED_FORMAT;
            $linkStyle = self::UNSUPPORTED_LINK_STYLE;
        }
        else if ($updateStatusEntry['current_rank'] > 1
            && $updateStatusEntry['last_security_rank'] != 0
            && $updateStatusEntry['last_security_rank'] < $updateStatusEntry['current_rank']) {
            $style = self::SECURITY_STYLE;
            $format = self::VERSION_FORMAT;
            $linkStyle = self::SECURITY_LINK_STYLE;
        }
        else if ($updateStatusEntry['current_rank'] > 1
            && $updateStatusEntry['last_bug_rank'] != 0
            && $updateStatusEntry['last_bug_rank'] < $updateStatusEntry['current_rank']) {
            $style = self::BUG_STYLE;
            $format = self::VERSION_FORMAT;
            $linkStyle = self::BUG_LINK_STYLE;
        }

        return '<tr>' .
            $this->format_string(self::MODULE_FORMAT, array('%color'=>$style,'%link_style'=>$linkStyle,'%project'=>$machineName,'%label'=>$moduleName ,'%label'=>$moduleName )) .
            $this->format_string($format, array('%color'=>$style,'%link_style'=>$linkStyle,'%project'=>$machineName,'%label'=>$installedVersion,'%version'=>$installedVersion)) .
            $this->format_string($format, array('%color'=>$style,'%link_style'=>$linkStyle,'%project'=>$machineName,'%label'=>$lastSecurityUpdateVersion,'%version'=>$lastSecurityUpdateVersion)) .
            $this->format_string($format, array('%color'=>$style,'%link_style'=>$linkStyle,'%project'=>$machineName,'%label'=>$lastBugFixVersion,'%version'=>$lastBugFixVersion)) .
        '</tr>';
    }

    /**
     * @param $string
     * @param array $args
     * @return string
     */
    private function format_string($string, array $args = array()) {
        // Transform arguments before inserting them.
        foreach ($args as $key => $value) {
            switch ($key[0]) {
                case '%':
                    // Escaped only.
                    $args[$key] =  htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    break;
                case '!':
                    // Pass-through.
            }
        }
        return strtr($string, $args);
    }

} 
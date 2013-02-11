<?php

    // In this script we simulate a system call. To be compatible with shared hosts
    // we don't use the php function system() as the use of this function is not
    // always allowed.
    
    $argv[] = "delegator=cron";
    $argv[] = "action=add";
    $argv[] = "resource=all";

    $argc = $argc + 3;
    
    require_once("../c/php/controller.php");
    
    $crondir = CRONDIR;
    
    $crontab = <<<EOF
*/2   *     *    *    *    run-parts ${crondir}scron.m.02
*/5   *     *    *    *    run-parts ${crondir}scron.m.05
*/10  *     *    *    *    run-parts ${crondir}scron.m.10
*/15  *     *    *    *    run-parts ${crondir}scron.m.15
*/30  *     *    *    *    run-parts ${crondir}scron.m.30
*     */1   *    *    *    run-parts ${crondir}scron.h.01
*     */2   *    *    *    run-parts ${crondir}scron.h.02
*     */4   *    *    *    run-parts ${crondir}scron.h.04
*     */8   *    *    *    run-parts ${crondir}scron.h.08
*     */12  *    *    *    run-parts ${crondir}scron.h.12
40    4     */1  *    *    run-parts ${crondir}scron.d.01
EOF;
    
    echo "\nAdd this to your crontab: \n";
    echo "# ------------------------------------------------\n";
    echo $crontab;
    echo "\n# ------------------------------------------------\n";
    echo "Note: these are the crons shipped with OpenSpaceLint. If you added schedules yourself add them too.";
    echo "\n\n";
?>
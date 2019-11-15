<?php
/**
 *  ==================================================================
 *        文 件 名: wsl2.php
 *        概    要: WSL2端口转发
 *        作    者: IT小强
 *        创建时间: 2019-11-11 16:48
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */
try {
    $wsl2Port = shell_exec('bash.exe -c "ifconfig eth0 | grep \'inet \'"');
    if (empty($wsl2Port)) {
        die('获取IP失败');
    }
    if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $wsl2Port, $match)) {
        die('IP解析失败');
    }
    $addr    = '0.0.0.0';
    $ip      = $match[0];
    $ports   = [3306, 80];
    $ports_a = implode(',', $ports);

    $wsl2DisplayName = 'WSL 2 Firewall Unlock';

    // Remove Firewall Exception Rules
    $del = shell_exec("PowerShell.exe Remove-NetFireWallRule -DisplayName '{$wsl2DisplayName}'");
    // adding Exception Rules for inbound and outbound Rules
    $out = shell_exec("PowerShell.exe New-NetFireWallRule -DisplayName '{$wsl2DisplayName}' -Direction Outbound -LocalPort {$ports_a} -Action Allow -Protocol TCP");
    $in  = shell_exec("PowerShell.exe New-NetFireWallRule -DisplayName '{$wsl2DisplayName}' -Direction Inbound -LocalPort {$ports_a}  -Action Allow -Protocol TCP");

    $info = [];
    foreach ($ports as $port) {
        $delete = shell_exec("PowerShell.exe netsh interface portproxy delete v4tov4 listenport={$port} listenaddress={$addr}");
        $add    = shell_exec("PowerShell.exe netsh interface portproxy add v4tov4 listenport=$port listenaddress=$addr connectport={$port} connectaddress={$ip}");
        $info[] = compact('port', 'delete', 'add');
    }
    $time = date('Y-m-d H:i:s');
    $data = compact('time', 'ip', 'ports', 'del', 'out', 'in', 'info');
    $path = realpath(__DIR__) . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
    $file = $path . date('Y-m-d') . '.json';
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
} catch (Exception $exception) {
    die($exception->getMessage());
}
exit('Succeed!');


# Ys7
萤石云 thinkphp5.1 简易SDK

        $ys7 = new Client(appKey,appSecret);
        echo "AccessToken=".$ys7->getAccessToken() . "\n";
        echo "Camera List:\n";
        print_r($ys7->getCameraList());

        echo "测试设备信息\n";
        print_r($ys7->getCameraInfo('203751922'));

        echo "测试设备信息\n";
        print_r($ys7->addCamera('825959238','SWUHXW'));

<?php
defined('IN_IA') or die('Access Denied');
class Yihe_jifenbaoModuleProcessor extends WeModuleProcessor
{
    public function respond()
    {
        global $_W;
        load()->model('mc');
        $poster = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_poster') . ' WHERE weid = :weid', array(':weid' => $_W['uniacid']));
        $fans = mc_fetch($this->message['from']);
        if (empty($fans['nickname']) || empty($fans['avatar'])) {
            $openid = $this->message['from'];
            $ACCESS_TOKEN = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$ACCESS_TOKEN}&openid={$openid}&lang=zh_CN";
            load()->func('communication');
            $json = ihttp_get($url);
            $userInfo = @json_decode($json['content'], true);
            $fans['nickname'] = $userInfo['nickname'];
            $fans['avatar'] = $userInfo['headimgurl'];
            $fans['province'] = $userInfo['province'];
            $fans['city'] = $userInfo['city'];
            mc_update($this->message['from'], array('nickname' => $mc['nickname'], 'avatar' => $mc['avatar']));
        }
        if (!empty($poster)) {
            if ($poster['starttime'] > time()) {
                return $this->postText($this->message['from'], $poster['nostarttips']);
            } elseif ($poster['endtime'] < time()) {
                return $this->postText($this->message['from'], $poster['endtips']);
            }
        }
        if ($this->message['content'] == '肯定好友') {
            $cfg = $this->module['config'];
            if ($cfg['locationtype'] == 0) {
                $ip = $this->GetIpLookup(CLIENT_IP);
                $city = $ip['city'];
                mc_update($fans['uid'], array('residecity' => $city));
                $pos = stripos($cfg['city'], $city);
                if ($pos === false) {
                    $nzmsg = '抱歉!

本次活动只针对【' . $cfg['city'] . '】微信用户开放

您所在的位置【' . $city . '】未开启活动，您不能参与本次活动，感谢您的支持!';
                    $this->postText($this->message['from'], $nzmsg);
                    die;
                }
            } elseif ($cfg['locationtype'] == 1 || $cfg['locationtype'] == 2) {
                $user = mc_fetch($this->message['from']);
                $city = $user['residecity'];
                $pos = stripos($cfg['city'], $city);
                if ($pos === false) {
                    $dqurl = '<a href=\'' . $_W['siteroot'] . str_replace('./', 'app/', $this->createMobileurl('diqu', array('uid' => $fans['uid']))) . '\'>点击这里</a>';
                    $dqmsg = '次活动只针对【' . $cfg['city'] . '】微信用户开放

当前地区为【' . $city . '】

如果你是该地区的用户，' . $dqurl . '验证

如果不处于此地区，暂时不能参与活动，感谢您的支持！';
                    $this->postText($this->message['from'], $dqmsg);
                    die;
                } else {
                }
            }
            $from_user = $this->message['content'];
            $credit1 = pdo_fetch('select * from ' . tablename('mc_credits_record') . ' where uniacid=:uniacid and uid=:uid and credittype=:credittype and remark=:remark', array(':uniacid' => $_W['uniacid'], ':uid' => $fans['uid'], ':credittype' => 'credit1', ':remark' => '关注送积分'));
            $credit2 = pdo_fetch('select * from ' . tablename('mc_credits_record') . ' where uniacid=:uniacid and uid=:uid and credittype=:credittype and remark=:remark', array(':uniacid' => $_W['uniacid'], ':uid' => $fans['uid'], ':credittype' => 'credit2', ':remark' => '关注送余额'));
            if ($_W['account']['level'] == 4) {
                if ($poster['kdtype'] == 0) {
                    $this->postText($this->message['from'], '商家未开启该功能！');
                    die;
                }
                if (empty($credit1) || empty($credit1)) {
                    $share = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_share') . ' WHERE weid = :weid and openid=:openid', array(':weid' => $_W['uniacid'], ':openid' => $fans['uid']));
                    if ($poster['score'] > 0 || $poster['scorehb'] > 0) {
                        $info1 = str_replace('#昵称#', $fans['nickname'], $poster['ftips']);
                        $info1 = str_replace('#积分#', $poster['score'], $info1);
                        $info1 = str_replace('#元#', $poster['scorehb'], $info1);
                        if ($poster['score']) {
                            mc_credit_update($share['openid'], 'credit1', $poster['score'], array($share['openid'], '关注送积分'));
                        }
                        if ($poster['scorehb']) {
                            mc_credit_update($share['openid'], 'credit2', $poster['scorehb'], array($share['openid'], '关注送余额'));
                        }
                        $this->postText($this->message['from'], $info1);
                    }
                    if ($share['helpid'] == 0) {
                        die;
                    }
                    $hmember = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_share') . ' WHERE weid = :weid and openid=:openid', array(':weid' => $_W['uniacid'], ':openid' => $share['helpid']));
                    if ($poster['cscore'] > 0 || $poster['cscorehb'] > 0) {
                        if ($hmember['status'] == 1) {
                            die;
                        }
                        $info2 = str_replace('#昵称#', $fans['nickname'], $poster['utips']);
                        $info2 = str_replace('#积分#', $poster['cscore'], $info2);
                        $info2 = str_replace('#元#', $poster['cscorehb'], $info2);
                        if ($poster['cscore']) {
                            mc_credit_update($hmember['openid'], 'credit1', $poster['cscore'], array($hmember['openid'], '2级推广奖励'));
                        }
                        if ($poster['cscorehb']) {
                            mc_credit_update($hmember['openid'], 'credit2', $poster['cscorehb'], array($hmember['openid'], '2级推广奖励'));
                        }
                        $this->postText($hmember['from_user'], $info2);
                    }
                    if ($poster['pscore'] > 0 || $poster['pscorehb'] > 0) {
                        $fmember = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_share') . ' WHERE weid = :weid and openid=:openid', array(':weid' => $_W['uniacid'], ':openid' => $hmember['helpid']));
                        if ($fmember['status'] == 1) {
                            die;
                        }
                        if ($fmember) {
                            $info3 = str_replace('#昵称#', $fans['nickname'], $poster['utips2']);
                            $info3 = str_replace('#积分#', $poster['cscore'], $info3);
                            $info3 = str_replace('#元#', $poster['cscorehb'], $info3);
                            if ($poster['pscore']) {
                                mc_credit_update($fmember['openid'], 'credit1', $poster['pscore'], array($hmember['openid'], '3级推广奖励'));
                            }
                            if ($poster['pscore']) {
                                mc_credit_update($fmember['openid'], 'credit2', $poster['pscore'], array($hmember['openid'], '3级推广奖励'));
                            }
                            $this->postText($fmember['from_user'], $info3);
                        }
                    }
                    die;
                } else {
                    $kdmsg = '尊敬的粉丝：\\n\\n您已经领取过积分了，不能重复领取，快去生成海报赚取积分吧！';
                    $this->postText($this->message['from'], $kdmsg);
                    die;
                }
            }
            if (empty($credit1) || empty($credit1)) {
                $urljq = '<a href=\'' . $_W['siteroot'] . str_replace('./', 'app/', $this->createMobileurl('kending', array('uid' => $fans['uid']))) . '\'>点击这里</a>';
                $kdmsg = '尊敬的粉丝：
需要点击领取奖励才能获得奖励哦
' . $urljq . '领取';
            } else {
                $kdmsg = '尊敬的粉丝：\\n\\n您已经领取过积分了，不能重复领取，快去生成海报赚取积分吧！';
            }
            $this->postText($this->message['from'], $kdmsg);
            die;
        }
        if ($this->message['msgtype'] == 'event') {
            $scene_id = str_replace('qrscene_', '', $this->message['eventkey']);
            $fans = mc_fetch($this->message['from']);
            if (empty($fans['nickname']) || empty($fans['avatar'])) {
                $openid = $this->message['from'];
                $ACCESS_TOKEN = $this->getAccessToken();
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$ACCESS_TOKEN}&openid={$openid}&lang=zh_CN";
                load()->func('communication');
                $json = ihttp_get($url);
                $userInfo = @json_decode($json['content'], true);
                $fans['nickname'] = $userInfo['nickname'];
                $fans['avatar'] = $userInfo['headimgurl'];
                $fans['province'] = $userInfo['province'];
                $fans['city'] = $userInfo['city'];
                mc_update($this->message['from'], array('nickname' => $mc['nickname'], 'avatar' => $mc['avatar']));
            }
            if ($this->message['event'] == 'subscribe') {
                $hmember = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_share') . ' WHERE weid = :weid and sceneid=:sceneid', array(':weid' => $_W['uniacid'], ':sceneid' => $scene_id));
                $member = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_share') . ' WHERE weid = :weid and from_user=:from_user', array(':weid' => $_W['uniacid'], ':from_user' => $this->message['from']));
                if (empty($member)) {
                    pdo_insert($this->modulename . '_share', array('openid' => $fans['uid'], 'nickname' => $fans['nickname'], 'avatar' => $fans['avatar'], 'pid' => $poster['id'], 'createtime' => time(), 'helpid' => $hmember['openid'], 'weid' => $_W['uniacid'], 'score' => $poster['score'], 'cscore' => $poster['cscore'], 'pscore' => $poster['pscore'], 'from_user' => $this->message['from'], 'follow' => 1));
                    $share['id'] = pdo_insertid();
                    $share = pdo_fetch('select * from ' . tablename($this->modulename . '_share') . " where id='{$share['id']}'");
                    if ($poster['kdtype'] == 1) {
                        if (!empty($hmember['from_user'])) {
                            $mcsj = mc_fetch($hmember['from_user']);
                            $msgsj = '您已通过「' . $mcsj['nickname'] . '」，成功关注，点击下方

「菜单-领取奖励」

为好友加分';
                        } else {
                            $msgsj = '您需要点击「领取奖励」才能得到积分哦!';
                        }
                        $this->postText($this->message['from'], $msgsj);
                        die;
                    }
                    if ($poster['score'] > 0 || $poster['scorehb'] > 0) {
                        $info1 = str_replace('#昵称#', $fans['nickname'], $poster['ftips']);
                        $info1 = str_replace('#积分#', $poster['score'], $info1);
                        $info1 = str_replace('#元#', $poster['scorehb'], $info1);
                        if ($poster['score']) {
                            mc_credit_update($share['openid'], 'credit1', $poster['score'], array($share['openid'], '关注送积分'));
                        }
                        if ($poster['scorehb']) {
                            mc_credit_update($share['openid'], 'credit2', $poster['scorehb'], array($share['openid'], '关注送余额'));
                        }
                        $this->postText($this->message['from'], $info1);
                    }
                    if ($poster['cscore'] > 0 || $poster['cscorehb'] > 0) {
                        if ($hmember['status'] == 1) {
                            die;
                        }
                        $info2 = str_replace('#昵称#', $fans['nickname'], $poster['utips']);
                        $info2 = str_replace('#积分#', $poster['cscore'], $info2);
                        $info2 = str_replace('#元#', $poster['cscorehb'], $info2);
                        if ($poster['cscore']) {
                            mc_credit_update($hmember['openid'], 'credit1', $poster['cscore'], array($hmember['openid'], '2级推广奖励'));
                        }
                        if ($poster['cscorehb']) {
                            mc_credit_update($hmember['openid'], 'credit2', $poster['cscorehb'], array($hmember['openid'], '2级推广奖励'));
                        }
                        $this->postText($hmember['from_user'], $info2);
                    }
                    if ($poster['pscore'] > 0 || $poster['pscorehb'] > 0) {
                        $fmember = pdo_fetch('SELECT * FROM ' . tablename('yihe_jifenbao_share') . ' WHERE weid = :weid and openid=:openid', array(':weid' => $_W['uniacid'], ':openid' => $hmember['helpid']));
                        if ($fmember['status'] == 1) {
                            die;
                        }
                        if ($fmember) {
                            $info3 = str_replace('#昵称#', $fans['nickname'], $poster['utips2']);
                            $info3 = str_replace('#积分#', $poster['pscore'], $info3);
                            $info3 = str_replace('#元#', $poster['pscorehb'], $info3);
                            if ($poster['pscore']) {
                                mc_credit_update($fmember['openid'], 'credit1', $poster['pscore'], array($hmember['openid'], '3级推广奖励'));
                            }
                            if ($poster['pscorehb']) {
                                mc_credit_update($fmember['openid'], 'credit2', $poster['pscorehb'], array($hmember['openid'], '3级推广奖励'));
                            }
                            $this->postText($fmember['from_user'], $info3);
                        }
                    }
                } else {
                    $this->postText($this->message['from'], '亲，您已经是会员了，快去生成海报赚取奖励吧');
                }
                return $this->PostNews($poster, $fans['nickname']);
            }
            if ($this->message['event'] == 'SCAN') {
                $msg1 = $fans['nickname'] . '你已经是【' . $_W['account']['name'] . '】的会员了，不用再扫了哦。

你当前有' . $fans['credit1'] . '积分';
                $this->postText($this->message['from'], $msg1);
                return $this->PostNews($poster, $fans['nickname']);
            }
        }
        if ($this->message['msgtype'] == 'text' || $this->message['event'] == 'CLICK') {
            $cfg = $this->module['config'];
            if ($cfg['locationtype'] == 0) {
                $ip = $this->GetIpLookup(CLIENT_IP);
                $city = $ip['city'];
                mc_update($fans['uid'], array('residecity' => $city));
                $pos = stripos($cfg['city'], $city);
                if ($pos === false) {
                    $nzmsg = '抱歉!

本次活动只针对【' . $cfg['city'] . '】微信用户开放

您所在的位置【' . $city . '】未开启活动，您不能参与本次活动，感谢您的支持!';
                    $this->postText($this->message['from'], $nzmsg);
                    die;
                }
            } elseif ($cfg['locationtype'] == 1 || $cfg['locationtype'] == 2) {
                $user = mc_fetch($this->message['from']);
                $city = $user['residecity'];
                $pos = stripos($cfg['city'], $city);
                if ($pos === false) {
                    $dqurl = '<a href=\'' . $_W['siteroot'] . str_replace('./', 'app/', $this->createMobileurl('diqu', array('uid' => $fans['uid']))) . '\'>点击这里</a>';
                    $dqmsg = '本次活动只针对【' . $cfg['city'] . '】微信用户开放

当前地区为【' . $city . '】

如果你是该地区的用户，' . $dqurl . '验证

如果不处于此地区，暂时不能参与活动，感谢您的支持！';
                    $this->postText($this->message['from'], $dqmsg);
                    die;
                }
            }
            if ($poster['winfo1']) {
                $info = str_replace('#时间#', date('Y-m-d H:i', time() + 30 * 24 * 3600), $poster['winfo1']);
                $this->postText($this->message['from'], $info);
            }
            if ($poster['winfo2']) {
                $hbshare = pdo_fetch('select * from ' . tablename($this->modulename . '_share') . " where openid='{$fans['uid']}' ");
                $url = '<a href=\'' . $_W['siteroot'] . str_replace('./', 'app/', $this->createMobileurl('hbshare', array('type' => $poster['type'], 'id' => $hbshare['id']))) . '\'>查看你的专属二维码</a>';
                $msg2 = $poster['winfo2'] . '\\n\\n' . $url;
                if ($poster['rtype'] && $poster['type'] == 2) {
                }
                sleep(1);
                $this->postText($this->message['from'], $msg2);
            }
            $img = $this->createPoster($fans, $poster);
            $media_id = $this->uploadImage($img);
            $this->sendImage($this->message['from'], $media_id);
            die;
        }
    }
    public function sendImage($openid, $media_id)
    {
        $data = array('touser' => $openid, 'msgtype' => 'image', 'image' => array('media_id' => $media_id));
        $ret = $this->postRes($this->getAccessToken(), json_encode($data));
        return $ret;
    }
    public function GetIpLookup($ip = '')
    {
        if (empty($ip)) {
            $ip = GetIp();
        }
        $res = @file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip=' . $ip);
        if (empty($res)) {
            return false;
        }
        $jsonMatches = array();
        preg_match('#\\{.+?\\}#', $res, $jsonMatches);
        if (!isset($jsonMatches[0])) {
            return false;
        }
        $json = json_decode($jsonMatches[0], true);
        if (isset($json['ret']) && $json['ret'] == 1) {
            $json['ip'] = $ip;
            unset($json['ret']);
        } else {
            return false;
        }
        return $json;
    }
    private function uploadImage($img)
    {
        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $this->getAccessToken() . '&type=image';
        $post = array('media' => '@' . $img);
        load()->func('communication');
        $ret = ihttp_request($url, $post);
        $content = @json_decode($ret['content'], true);
        return $content['media_id'];
    }
    private $sceneid = 0;
    private $Qrcode = '/addons/yihe_jifenbao/qrcode/mposter#sid#.jpg';
    private function createPoster($fans, $poster)
    {
        global $_W;
        $bg = $poster['bg'];
        $pid = $poster['id'];
        $share = pdo_fetch('select * from ' . tablename($this->modulename . '_share') . " where openid='{$fans['uid']}' limit 1");
        if (empty($share)) {
            pdo_insert($this->modulename . '_share', array('openid' => $fans['uid'], 'nickname' => $fans['nickname'], 'avatar' => $fans['avatar'], 'pid' => $poster['id'], 'createtime' => time(), 'parentid' => 0, 'weid' => $_W['uniacid'], 'score' => $poster['score'], 'cscore' => $poster['cscore'], 'pscore' => $poster['pscore'], 'from_user' => $this->message['from'], 'follow' => 1));
            $share['id'] = pdo_insertid();
            $share = pdo_fetch('select * from ' . tablename($this->modulename . '_share') . " where id='{$share['id']}'");
        }
        $qrcode = str_replace('#sid#', $share['id'], IA_ROOT . $this->Qrcode);
        $data = json_decode(str_replace('&quot;', '\'', $poster['data']), true);
        include 'func.php';
        set_time_limit(0);
        @ini_set('memory_limit', '256M');
        $size = getimagesize(tomedia($bg));
        $target = imagecreatetruecolor($size[0], $size[1]);
        $bg = imagecreates(tomedia($bg));
        imagecopy($target, $bg, 0, 0, 0, 0, $size[0], $size[1]);
        imagedestroy($bg);
        foreach ($data as $value) {
            $value = trimPx($value);
            if ($value['type'] == 'qr') {
                if ($poster['type'] == 2) {
                    $url = $this->getQR($fans, $poster, $share['id']);
                } elseif ($poster['type'] == 3) {
                    $url = $_W['siteroot'] . str_replace('./', 'app/', $this->createMobileurl('sharetz', array('weid' => $_W['uniacid'], 'uid' => $fans['uid'])));
                }
                if (!empty($url)) {
                    $img = IA_ROOT . '/temp_qrcode.png';
                    include 'phpqrcode.php';
                    $errorCorrectionLevel = 'L';
                    $matrixPointSize = '4';
                    QRcode::png($url, $img, $errorCorrectionLevel, $matrixPointSize, 2);
                    mergeImage($target, $img, array('left' => $value['left'], 'top' => $value['top'], 'width' => $value['width'], 'height' => $value['height']));
                    @unlink($img);
                }
            } elseif ($value['type'] == 'img') {
                $img = saveImage($fans['avatar']);
                mergeImage($target, $img, array('left' => $value['left'], 'top' => $value['top'], 'width' => $value['width'], 'height' => $value['height']));
                @unlink($img);
            } elseif ($value['type'] == 'name') {
                mergeText($this->modulename, $target, $fans['nickname'], array('size' => $value['size'], 'color' => $value['color'], 'left' => $value['left'], 'top' => $value['top']), $poster);
            }
        }
        imagejpeg($target, $qrcode);
        imagedestroy($target);
        return $qrcode;
    }
    private function getQR($fans, $poster, $sid)
    {
        global $_W;
        $pid = $poster['id'];
        if (empty($this->sceneid)) {
            $share = pdo_fetch('select * from ' . tablename($this->modulename . '_share') . " where id='{$sid}'");
            if (!empty($share['url'])) {
                $out = false;
                if ($poster['rtype']) {
                    $qrcode = pdo_fetch('select * from ' . tablename('qrcode') . " where uniacid='{$_W['uniacid']}' and qrcid='{$share['sceneid']}' " . " and name='{$poster['title']}' and ticket='{$share['ticketid']}' and url='{$share['url']}'");
                    if ($qrcode['createtime'] + $qrcode['expire'] < time()) {
                        pdo_delete('qrcode', array('id' => $qrcode['id']));
                        $out = true;
                    }
                }
                if (!$out) {
                    $this->sceneid = $share['sceneid'];
                    return $share['url'];
                }
            }
            $this->sceneid = pdo_fetchcolumn('select sceneid from ' . tablename($this->modulename . '_share') . " where weid='{$_W['uniacid']}' order by sceneid desc limit 1");
            if (empty($this->sceneid)) {
                $this->sceneid = 1000001;
            } else {
                $this->sceneid++;
            }
            $barcode['action_info']['scene']['scene_id'] = $this->sceneid;
            load()->model('account');
            $acid = pdo_fetchcolumn('select acid from ' . tablename('account') . " where uniacid={$_W['uniacid']}");
            $uniacccount = WeAccount::create($acid);
            $time = 0;
            $barcode['action_name'] = 'QR_SCENE';
            $barcode['expire_seconds'] = 30 * 24 * 3600;
            $res = $uniacccount->barCodeCreateDisposable($barcode);
            $time = $barcode['expire_seconds'];
            $rid = $this->rule;
            $sql = 'SELECT * FROM ' . tablename('rule_keyword') . ' WHERE `rid`=:rid LIMIT 1';
            $row = pdo_fetch($sql, array(':rid' => $rid));
            pdo_insert('qrcode', array('uniacid' => $_W['uniacid'], 'acid' => $acid, 'qrcid' => $this->sceneid, 'name' => $poster['title'], 'keyword' => $row['content'], 'model' => 1, 'ticket' => $res['ticket'], 'expire' => $time, 'createtime' => time(), 'status' => 1, 'url' => $res['url']));
            pdo_update($this->modulename . '_share', array('sceneid' => $this->sceneid, 'ticketid' => $res['ticket'], 'url' => $res['url'], 'nickname' => $fans['nickname'], 'avatar' => $fans['avatar']), array('id' => $sid));
            return $res['url'];
        }
    }
    public function postText($openid, $text)
    {
        $post = '{"touser":"' . $openid . '","msgtype":"text","text":{"content":"' . $text . '"}}';
        $ret = $this->postRes($this->getAccessToken(), $post);
        return $ret;
    }
    private function postRes($access_token, $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
        load()->func('communication');
        $ret = ihttp_request($url, $data);
        $content = @json_decode($ret['content'], true);
        return $content['errcode'];
    }
    private function PostNews($poster, $name)
    {
        $stitle = unserialize($poster['stitle']);
        if (!empty($stitle)) {
            $thumbs = unserialize($poster['sthumb']);
            $sdesc = unserialize($poster['sdesc']);
            $surl = unserialize($poster['surl']);
            foreach ($stitle as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $response[] = array('title' => str_replace('#昵称#', $name, $value), 'description' => $sdesc[$key], 'picurl' => tomedia($thumbs[$key]), 'url' => $this->buildSiteUrl($surl[$key]));
            }
            if ($response) {
                return $this->respNews($response);
            }
        }
        return '';
    }
    private function getAccessToken()
    {
        global $_W;
        load()->model('account');
        $acid = $_W['acid'];
        if (empty($acid)) {
            $acid = $_W['uniacid'];
        }
        $account = WeAccount::create($acid);
        $token = $account->fetch_available_token();
        return $token;
    }
}
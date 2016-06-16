<?php
/**
 * 积分宝模块微站定义
 *
 * @author 老虎
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');
require_once IA_ROOT .'/addons/yihe_jifenbao/lib/PHPZIP.php';
class Yihe_jifenbaoModule extends WeModule {
    public $reply = 'yihe_jifenbao_poster';
    public function fieldsFormDisplay($rid = 0) {
        load ()->func ( 'tpl' );
		global $_W;
		if (!empty($rid)) {
            $item = pdo_fetch("SELECT * FROM " . tablename($this->reply) . " WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));
        }
        if ($item){
			$data = json_decode(str_replace('&quot;', "'", $item['data']), true);
			$size = getimagesize(toimage($item['bg']));
			$size = array($size[0]/2,$size[1]/2);
			$date = array('start'=>date('Y-m-d H:i:s',$item['starttime']),'end'=>date('Y-m-d H:i:s',$item['endtime']));
			$titles = unserialize($item['stitle']);
			$thumbs = unserialize($item['sthumb']);
			$sdesc = unserialize($item['sdesc']);
			$surl = unserialize($item['surl']);
			foreach ($titles as $key => $value) {
				if (empty($value)) continue;
				$slist[] = array('stitle'=>$value,'sdesc'=>$sdesc[$key],'sthumb'=>$thumbs[$key],'surl'=>$surl[$key]);
			}
		}
        $groups = pdo_fetchall('select * from '.tablename('mc_groups')." where uniacid='{$_W['uniacid']}' order by isdefault desc");
        include $this->template('form');
	}

    public function fieldsFormSubmit($rid) {
		global $_GPC, $_W;
        $key=str_replace('[', "", $_GPC['keywords']);
        $key=str_replace(']', "",$key);
        $key=str_replace('&quot;', '"',$key);
        $keywords=json_decode($key, true);   
        //print_r($keywords);
       // exit;

        $id = intval($_GPC['reply_id']);
        $ques = $_GPC['ques'];
			$answer = $_GPC['answer'];
			$questions = '';
			foreach ($ques as $key => $value) {
				if (empty($value)) continue;
				$questions[] = array('question'=>$value,'answer'=>$answer[$key]);
			}
        $insert = array(
            'rid' => $rid,
			'weid'=> $_W['uniacid'],
            'title' => $_GPC ['title'],
            'type' => $_GPC ['type'],
            'bg' => $_GPC ['bg'],
            'data' => htmlspecialchars_decode($_GPC ['data']),
            'weid' => $_W ['uniacid'],
            'score' => $_GPC ['score'],
            'cscore' => $_GPC ['cscore'],
            'pscore' => $_GPC ['pscore'],
            'scorehb' => $_GPC ['scorehb'],
            'cscorehb' => $_GPC ['cscorehb'],
            'pscorehb' => $_GPC ['pscorehb'],
            'rscore' => $_GPC ['rscore'],
            'gid' => $_GPC ['gid'],
            'kdtype' => $_GPC ['kdtype'],
            'winfo1' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['winfo1']),ENT_QUOTES),
            'winfo2' => $_GPC ['winfo2'],
            'winfo3' => $_GPC ['winfo3'],
            'stitle' => serialize($_GPC ['stitle']),
            'sthumb' => serialize($_GPC ['sthumb']),
            'sdesc' => serialize($_GPC ['sdesc']),
            'rtips' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['rtips']),ENT_QUOTES),
            'ftips' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['ftips']),ENT_QUOTES),
            'utips' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['utips']),ENT_QUOTES),
            'utips2' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['utips2']),ENT_QUOTES),
            'wtips' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['wtips']),ENT_QUOTES),
            'nostarttips' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['nostarttips']),ENT_QUOTES),
            'endtips' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['endtips']),ENT_QUOTES),
            'starttime' => strtotime($_GPC['starttime']),
            'endtime' => strtotime($_GPC['endtime']),
            'surl' => serialize($_GPC ['surl']),
            'kword' => $_GPC ['kword'],
            'credit' => $_GPC ['credit'],
            'doneurl' => $_GPC ['doneurl'],
            'tztype' => $_GPC ['tztype'],
            'slideH' => $_GPC ['slideH'],
            'mbcolor' => $_GPC ['mbcolor'],
            'mbstyle' => $_GPC ['mbstyle'],
            'mbfont' => $_GPC ['mbfont'],
            'sliders' => $_GPC ['sliders'],
            'mtips' => $_GPC ['mtips'],            
            'sharetitle' => $_GPC ['sharetitle'],
            'sharethumb' => $_GPC ['sharethumb'],
            'sharedesc' => $_GPC ['sharedesc'],
            'sharegzurl' => $_GPC ['sharegzurl'],
            'tzurl' => $_GPC ['tzurl'],
            'questions' => serialize($questions),
            'createtime' =>time(),
        );
        if (empty($id)) {
            $id = pdo_insert($this->reply, $insert);
            //肯定好友
            $rule = array(
				'uniacid' => $_W['uniacid'],
                'name' => '肯定好友(这是设置好的，不要去修改)',
				'module' => $this->modulename,
				'status' => 1,
				'displayorder' => 253,
		    );
		    pdo_insert('rule',$rule);
            unset($rule['name']);
            $rule['type'] = 1;
		    $rule['rid'] = pdo_insertid();
		    $rule['content'] = '肯定好友';
		    pdo_insert('rule_keyword',$rule);
            //肯定好友结束
            //message('保存成功', 'refresh');
        } else {
            unset($insert['createtime']);
            pdo_update($this->reply, $insert, array('id' => $id));
            pdo_update('qrcode',array('keyword'=>$keywords['content'],'name'=>$_GPC ['title']),array('uniacid'=>$_W['uniacid']));
            //message('修改成功', 'refresh');
        }
	}

    public function ruleDeleted($rid) {
		//删除规则时调用，这里 $rid 为对应的规则编号
		pdo_delete($this->reply, array('rid' => $rid));
	}


	
	public function settingsDisplay($settings) {
		global $_GPC,$_W;
        load ()->func ( 'tpl' );
        !defined('APP_PATH') && define('APP_PATH', IA_ROOT . '/attachment/yihe_jifenbao/');
        !defined('APP_CLASS_PATH') && define('APP_CLASS_PATH', APP_PATH . '/');
		load()->func('file');
		if (checksubmit()) {
			$arr_json="";
			if(!empty($_FILES['nbfwpaycert']['tmp_name'])){
				$ext=pathinfo($_FILES['nbfwpaycert']['name'], PATHINFO_EXTENSION);
				if(strtolower($ext)!="zip"){
					message("[文件格式错误]请上传您的微信支付证书哦~", '', 'error');
				}
				$wxcertdir=APP_CLASS_PATH."cert/".$_W["uniacid"];
				if(!is_dir($wxcertdir)){
					mkdir($wxcertdir);
				}
				if(is_dir($wxcertdir)){
					if(!is_writable($wxcertdir)){
						message("请保证目录：[".APP_CLASS_PATH."]可写");
					}
				}
				$save_file=$wxcertdir."/".$_W["uniacid"].".".$ext;
				file_move($_FILES['nbfwpaycert']['tmp_name'], $save_file);
				$archive   = new PHPZIP();
				$archive->unzip($save_file,$wxcertdir); // 把zip中的文件解压到目录中
				$certpath=$wxcertdir."/apiclient_cert.pem";
				$keypath=$wxcertdir."/apiclient_key.pem";
				$arr=array("certpath"=>$certpath,"keypath"=>$keypath);
				$arr_json=json_encode($arr);
				file_delete($save_file);
			}
			if($arr_json==""){
				$arr_json=$settings["nbfwxpaypath"];
			}
			$cfg = array(
                'yihe_jifenbao_fansnum'=>$_GPC['yihe_jifenbao_fansnum'],
				'yihe_jifenbao_usr' => $_GPC['yihe_jifenbao_usr'],
                'nbfchangemoney' => $_GPC['nbfchangemoney'],
				'nbfhelpgeturl'=>$_GPC['nbfhelpgeturl'],
				'nbfwxpaypath'=>$arr_json,
                'mchid'=>$_GPC['mchid'],
                'apikey'=>$_GPC['apikey'],
                'appid'=>$_GPC['appid'],
                'secret'=>$_GPC['secret'],
                'szurl' => $_GPC ['szurl'],
                'szcolor' => $_GPC ['szcolor'],
                'rmb_num' => $_GPC ['rmb_num'],
                'day_num' => $_GPC ['day_num'],
                'txinfo' => htmlspecialchars_decode(str_replace('&quot;','&#039;',$_GPC ['txinfo']),ENT_QUOTES),
                'locationtype'=>$_GPC['locationtype'],
                'jiequan'=>$_GPC['jiequan'],
                'paihang'=>$_GPC['paihang'],
                'head'=>$_GPC['head'],
                'city'=>$_GPC['city']
			);
			if ($this->saveSettings($cfg)) {
				message('保存成功', 'refresh');
			}
		}
		include $this->template('settings');
	}
}
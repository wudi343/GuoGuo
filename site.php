<?php
/**
 * 建工裹裹模块微站定义
 *
 * @author 吴迪
 * @url http://bbs.we7.cc/
 */
defined('IN_IA') or exit('Access Denied');

class Ewei_expressModuleSite extends WeModuleSite {

	public function doWebExpress() {
		global $_W, $_GPC;	
		load()->func('tpl');
		
		$pindex = max(1, intval($_GPC['page']));
		$psize = 15;
		$condition = " 1";
		
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = TIMESTAMP;
		}
		if (!empty($_GPC['time'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']) + 86399;
			$condition .= " AND createtime >= FROM_UNIXTIME(:starttime) AND createtime <= FROM_UNIXTIME(:endtime) ";
			$paras[':starttime'] = $starttime;
			$paras[':endtime'] = $endtime;
		}
		if (!empty($_GPC['mobile'])) {
			$condition .= " AND mobile LIKE '%{$_GPC['mobile']}%'";
		}
		if (!empty($_GPC['code'])) {
			$condition .= " AND code LIKE '%{$_GPC['code']}%'";
		}
		if (!empty($_GPC['express_sn'])) {
			$condition .= " AND express_sn LIKE '%{$_GPC['express_sn']}%'";
		}
		if (isset($_GPC['status'])) {
			$condition .= " AND status = {$_GPC['status']}";
		}
			
		$sql = 'SELECT COUNT(*) FROM ' . tablename('ewei_express') . ' WHERE ' . $condition;
		$total = pdo_fetchcolumn($sql, $paras);
		$list_count_0 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_express') . "  WHERE status=0");
		$list_count_1 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_express') . "  WHERE status=1");
		$list_count_2 = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('ewei_express') . "  WHERE status=2");
		$list_total = $list_count_0 + $list_count_1 + $list_count_2;

		
		if ($total > 0) {
			$limit = ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
			$sql = 'SELECT * FROM  '. tablename('ewei_express') . '  WHERE ' . $condition . ' ORDER BY id DESC ' . $limit;
			$list = pdo_fetchall($sql,$paras);
			
			$pager = pagination($total, $pindex, $psize);
		}
		
		include $this->template('Express');
	}
	
	public function doWebDelivery() {
		global $_W, $_GPC;	
		load()->func('tpl');
		
		if(!empty($_GPC['ajax'])){
			if($_GPC['ajax'] == 'price'){
				pdo_update('ewei_express_delivery', array('weight' => $_GPC['weight'],'price' => $_GPC['fee'],'status' => '等待付款'), array('id' => $_GPC['id']));
				//推送消息给用户
				$r = pdo_fetch("SELECT e.mobile,d.createtime FROM `ims_ewei_express_delivery` as d,`ims_ewei_express` as e where d.pid=e.id and d.id=".$_GPC['id']);
				
				$openid = $this->getMobileOpenid($r['mobile']);
				if($openid != ''){
					$data = array (
						'first' => array('value' => '您的送货上门订单计费完成，请支付！','color' => '#FF0000'),
						'keyword1' => array('value' => $_GPC['id']),
						'keyword2' => array('value' => $_GPC['fee'].'元(重量:'.$_GPC['weight'].'kg)'),
						'keyword3' => array('value' => $r['createtime']),
						'remark' => array('value' => '计费标准：3kg以下1元，续重0.5kg/元，不足0.5kg按0.5kg计算，最高10元封顶。您可以在菜单【建工裹裹】-【送货上门】查看，点击该订单下方的“去支付”按钮完成支付。','color' => '#008000'),
					);
					$acc = WeAccount::create($_W['acid']);
					$a=$acc->sendTplNotice($openid,'3FdLTW9fjkvCa_GwLZ6mXR_3S5lQDlm0G9_kOnrByBQ',$data,'http://weixin./app/index.php?i=6&c=entry&do=index&m=ewei_express&status=1');
				}
				
				echo 'ok';
			}elseif($_GPC['ajax'] == 'remark'){
				pdo_update('ewei_express_delivery', array('remark' => $_GPC['value']), array('id' => $_GPC['id']));
				echo 'ok';
			}
			exit;
		}
		
		$pindex = max(1, intval($_GPC['page']));
		$psize = 15;
		$condition = " 1";
		
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = TIMESTAMP;
		}
		if (!empty($_GPC['time'])) {
			$starttime = strtotime($_GPC['time']['start']);
			$endtime = strtotime($_GPC['time']['end']) + 86399;
			$condition .= " AND createtime >= FROM_UNIXTIME(:starttime) AND createtime <= FROM_UNIXTIME(:endtime) ";
			$paras[':starttime'] = $starttime;
			$paras[':endtime'] = $endtime;
		}
		if (!empty($_GPC['mobile'])) {
			$condition .= " AND usertel LIKE '%{$_GPC['mobile']}%'";
		}
		if (!empty($_GPC['code'])) {
			$condition .= " AND code LIKE '%{$_GPC['code']}%'";
		}
		if (!empty($_GPC['express_sn'])) {
			$condition .= " AND express_sn LIKE '%{$_GPC['express_sn']}%'";
		}
		if (!empty($_GPC['status'])) {
			$condition .= " AND status = '{$_GPC['status']}'";
		}

		$sql = 'SELECT COUNT(*) FROM view_express_deliverylist WHERE ' . $condition;
		$total = pdo_fetchcolumn($sql, $paras);
		$list_count_0 = pdo_fetchcolumn('SELECT COUNT(*) FROM view_express_deliverylist WHERE status="等待称重计费"');
		$list_count_1 = pdo_fetchcolumn('SELECT COUNT(*) FROM view_express_deliverylist WHERE status="等待付款"');
		$list_count_2 = pdo_fetchcolumn('SELECT COUNT(*) FROM view_express_deliverylist WHERE status="送货中"');
		$list_count_3 = pdo_fetchcolumn('SELECT COUNT(*) FROM view_express_deliverylist WHERE status="已完成"');
		$list_total = $list_count_0 + $list_count_1 + $list_count_2 + $list_count_3;
		
		if ($total > 0) {
			$limit = ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
			$sql = 'SELECT * FROM  view_express_deliverylist  WHERE ' . $condition . ' ORDER BY id DESC ' . $limit;
			$list = pdo_fetchall($sql,$paras);
			
			$pager = pagination($total, $pindex, $psize);
		}
		include $this->template('Delivery');
	}
	
    public function doWebemployee()//员工管理
    {

        $list      = pdo_fetchAll('SELECT * FROM ' . tablename('ewei_express_employee'));
		include $this->template('staff-list');
    }
    public function doWebstaffadd()
    {
		global $_W, $_GPC;
        $e_id = intval($_GPC['e_id']);
        if (!empty($e_id)) {
            $item      = pdo_fetch('SELECT * FROM ' . tablename('ewei_express_employee') . ' WHERE `e_id`=:e_id',array(
                'e_id' => $e_id
            ));
        }
        include $this->template('staff-add');
    }
	
    public function doWebstaffaddok()
    {
		global $_W, $_GPC;
        $e_id = intval($_GPC['e_id']);
		
        $e                 = array();
        $e['e_name']         = $_GPC['e_name'];
        $e['e_mobile']       = $_GPC['e_mobile'];
		$e['note']       	 = $_GPC['note'];
		
        if (!empty($e_id)) { //编辑
            pdo_update('ewei_express_employee', $e, array(
                'e_id' => $e_id
            ));
        }else{//新增
            pdo_insert('ewei_express_employee', $e);
		}
		message('操作成功!', $this->createWebUrl('employee', array('name'=>'ewei_express_employee')));
    }
	
    public function doWebstaffdelete()
    {
        global $_W, $_GPC;
        $e_id = intval($_GPC['e_id']);
        if (!empty($e_id)) {
            pdo_delete('ewei_express_employee', array(
                'e_id' => $e_id
            ));
            pdo_delete('ewei_express_employee', array(
                'e_id' => $e_id
            ));
        }
        message('操作成功!', referer());
    }
	
    public function doMobilestaffcheck()
    {
        global $_W, $_GPC;
        if (empty($_W['openid'])) {
            message("请在微信中访问");
        }else{
			$e = array();
			$e['e_openid'] = $_W['openid'];
		}
        $e_id = intval($_GPC['e_id']);
        if (!empty($e_id)) {
            pdo_update('ewei_express_employee', $e, array(
                'e_id' => $e_id
            ));
        }
        message('员工身份验证成功!');
    }
	
	
	public function doWebStatistics() {
		message("开发中...");
	}
	
	public function doMobileIndex() { //app首页
        global $_W, $_GPC;

        if (empty($_W['openid'])) {
            message("请在微信中访问");
			exit;
        }else{
			checkauth();
			$mobile_str = $this->getMobileList();
			
			$status = empty($_GPC['status']) ? 0 : trim($_GPC['status']);
			
			$lists = pdo_fetchall('SELECT * FROM ' . tablename('ewei_express') . ' WHERE `status` = ' . $status . ' AND `mobile` in ( ' .$mobile_str .' ) order by id desc');	
			include $this->template('index');
		}
	}
	
	public function doMobileCancelOrder() { //app取消订单
        global $_W, $_GPC;

        if (empty($_W['openid'])) {
            message("请在微信中访问");
			exit;
        }else{
			checkauth();
			$pid = intval($_GPC['pid']);
			
			pdo_delete('ewei_express_delivery', array('pid' => $pid));
			pdo_update('ewei_express', array('status' => 0), array('id' => $pid));
			
			
			message('订单取消成功。', $this->createMobileUrl('index',array('weid' => $row[weid], 'id' => $reid, 'status'=>1)), 'success');
		}
	}
	
	public function doMobileSetting() { //app用户设置
        include $this->template('Setting');
	}
	
	public function doMobileMobileManage() { //app用户手机管理
		global $_W, $_GPC;
		
		if($_GET['action'] === 'del'){//删除手机
			pdo_delete('ewei_express_phonenum', array('id' => intval($_GET['id']),'openid'=>$_W['openid']));
			message('解绑成功！', $this->createMobileUrl('mobilemanage',array('weid' => $row[weid])), 'success');
		}
		
		if($_POST){
			$arr = array();
			$arr['phonenum'] = trim($_POST['mobile']);
			$arr['openid'] = $_W['openid'];
			load()->model('utility');
			if(empty($arr['phonenum'])){message('手机号不能为空！', '', 'error');exit;}
			if($arr['phonenum'] == $_W['member']['mobile']){
				message('绑定手机不能和当前手机相同！', '', 'error');
				exit;
			}elseif (!code_verify($_W['uniacid'],$arr['phonenum'],trim($_POST['vcode']))) {
				message('验证码错误，请重新输入！', '', 'error');
				exit;
			}else{
				$result = pdo_insert('ewei_express_phonenum',$arr);
				if($result){
					message('绑定成功！', $this->createMobileUrl('mobilemanage'), 'success');
				}else{
					message('绑定失败，请重试！', '', 'error');
				}
			}
		}else{
			$openid = $_W['openid'];
			$lists = pdo_fetchall('SELECT * FROM ' . tablename('ewei_express_phonenum') . ' WHERE `openid` = "' . $openid . '"');	
			include $this->template('MobileManage');
		}
	}
	
	public function doMobileAddressManage() { //app用户地址管理
		global $_W, $_GPC;
		
		if($_GET['action'] === 'del'){//删除地址
			pdo_delete('ewei_express_address', array('id' => intval($_GET['id']),'openid'=>$_W['openid']));
			message('地址删除成功！', $this->createMobileUrl('addressmanage',array('weid' => $row[weid])), 'success');
		}
		
		if($_POST){
			if(empty($_POST['username'])){message('收件人姓名不能为空！', '', 'error');exit;}
			if(empty($_POST['usertel'])){message('收件人手机不能为空！', '', 'error');exit;}
			if(empty($_POST['building'])){message('楼栋不能为空！', '', 'error');exit;}
			if(empty($_POST['room'])){message('门牌号不能为空！', '', 'error');exit;}
			// if(!is_numeric($_POST['room'])){message('门牌号只能是数字，如201！', '', 'error');exit;}
			
			$arr = array();
			$arr['username'] = trim($_POST['username']);
			$arr['usertel'] = trim($_POST['usertel']);
			$arr['building'] = trim($_POST['building']);
			$arr['room'] = trim($_POST['room']);
			$arr['openid'] = $_W['openid'];
			
			$result = pdo_insert('ewei_express_address',$arr);
			
			if($_GET['action'] === 'ajax'){ //ajax提交(下单那个地方提交过来的)
				if($result){
					exit(pdo_insertid());
				}else{
					exit('error');
				}
			}else{
				if($result){
					message('地址添加成功！', $this->createMobileUrl('addressmanage'), 'success');
				}else{
					message('地址添加失败，请重试！', '', 'error');
				}				
			}
		}else{
			$lists = pdo_fetchall('SELECT * FROM ' . tablename('ewei_express_address') . ' WHERE `openid` = "' . $_W['openid'] . '"');	
			include $this->template('AddressManage');
		}
	}
	
	public function doMobileTeacherCert() { //app用户教师认证
		global $_W, $_GPC;
		$openid = $_W['openid'];
		
		if($_POST){
			if(empty($_POST['jgh'])){message('教工号不能为空！', '', 'error');exit;}
			if(empty($_POST['sfzh'])){message('身份证号不能为空！', '', 'error');exit;}
			if(empty($_POST['xm'])){message('姓名不能为空！', '', 'error');exit;}
			$jgh = trim($_POST['jgh']);
			$sfzh = trim($_POST['sfzh']);
			$xm = trim($_POST['xm']);
			$l = pdo_fetchall("SELECT * FROM " . tablename('ewei_express_teacher') . " WHERE jgh='".$jgh."' and sfzh='".$sfzh."' and xm='".$xm."'");
			if(empty($l)){
				message('认证失败，请重新认证！',$this->createMobileUrl('teachercert'),'error');
			}else{
				pdo_update('ewei_express_teacher', array('openid' => $openid), array('jgh' => $jgh));
				message('认证成功！', $this->createMobileUrl('teachercert'),'success');				
			}
		}
		
		$list = pdo_fetchall("SELECT * FROM " . tablename('ewei_express_teacher') . " WHERE openid = '" . $openid . "'");
		
		include $this->template('TeacherCert');		
	}
	
	public function doMobileSelfPick() { //app用户自取二维码
		global $_W, $_GPC;
		$pid = intval($_GET['pid']);
		$mobile_str = $this->getMobileList();	
		$openid = $_W['openid'];
		$result	= pdo_fetch("SELECT * FROM " . tablename('ewei_express') . " WHERE `id` = '".$pid."' and `mobile` in (" .$mobile_str .")");
		
		//计算代取任务的下一个ID
		$r = pdo_fetch('select MAX(id) as nextid from '.tablename('ewei_express_transfer'));
		$tid = $r['nextid']+1;
			
		if($result){
			$url = urlencode('http://weixin./app/index.php?i=6&c=entry&do=PackageInfo&m=ewei_express&sign='.base64_encode($pid.'-'.time()));
			include $this->template('SelfPick');
		}else{
			message('非法访问', '', 'error');
		}
	}
	
	public function doMobileTransfer() { //app代取
		global $_W, $_GPC;
		
		if (empty($_W['member']['mobile'])) {
			message('您不是新锐空间会员。请先关注新锐空间微信号，并注册为新锐空间会员。');
		}else{
			checkauth();
		}
		
		if($_GET['action'] == 'ajax'){
			$transfer = intval($_POST['transfer']);
			$pid =  intval($_POST['id']);
			if($transfer){//新的一次分享
				$arr = array();
				$arr['pid'] = $pid;
				pdo_insert('ewei_express_transfer',$arr);
			}else{//取消代取
				pdo_update('ewei_express_transfer', array('status'=>-1), array('pid' => $pid));
			}
			pdo_update('ewei_express', array('transfer'=>$transfer), array('id' => $pid));
			exit;
		}		
			
		if($_GET['action'] == 'receive_accept'){//接受代取委托
			$tid =  intval($_GET['tid']);
			$r = pdo_fetch("SELECT * FROM " . tablename('ewei_express_transfer') . " WHERE `id` = '".$tid."'");
			
			if($r['t_openid'] == ''){
				pdo_update('ewei_express_transfer', array('status'=>1,'t_openid'=>$_W['openid'],'t_mobile'=>$_W['member']['mobile'],'modifytime'=>date('Y-m-d H:i:s')), array('id' => $tid));
				//推送消息给原主人
				$r = pdo_fetch("SELECT e.mobile FROM `ims_ewei_express_transfer` as t,`ims_ewei_express` as e where t.pid=e.id and t.id=".$tid);
				$openid = $this->getMobileOpenid($r['mobile']);
				if($openid != ''){
					$data = array (
						'first' => array('value' => '您好，您发出的代取快递委托已被接受。','color' => '#FF0000'),
						'keyword1' => array('value' => $_W['member']['mobile']),
						'keyword2' => array('value' => date('Y-m-d H:i:s')),
						'keyword3' => array('value' => '￥0.00元'),
						'keyword4' => array('value' => '7天'),
						'remark' => array('value' => '如需取消代取，请点击菜单【建工裹裹】-【自取快递】-【我要自取】，按提示操作即可。','color' => '#008000'),
					);
					$acc = WeAccount::create($_W['acid']);
					$acc->sendTplNotice($openid,'IDP6R5n9Wol9JRHLrSZqK9j0XXwM0w3ZjwQ7J6jXzD178',$data);
				}
				
				message('代取委托接受成功！', referer(), 'success');
			}else{//已经有数据
				if($r['t_openid'] != $_W['openid']){//接受以后再来看
					message('非法访问-1', '', 'error');
				}
			}
			exit;
		}
			
		$pid = intval($_GET['pid']);	//包裹id
		$tid = intval($_GET['tid']);	//代取任务id
		
		$result	= pdo_fetch("SELECT transfer FROM " . tablename('ewei_express') . " WHERE `id` = '".$pid."'");

		if($result){
			if($result['transfer'] == 1){//已经分享过
				$r = pdo_fetch("SELECT * FROM " . tablename('ewei_express_transfer') . " WHERE `id` = '".$tid."'");
				if(!$r){
					message('非法访问-2', '', 'error');
					exit;
				}elseif($r['status'] == '-1'){
					message('收件人取消了本次代取委托。', '', 'error');
					exit;
				}elseif($r['status'] == '2' && $r['t_openid'] != $_W['openid']){
					message('本次代取委托已失效。', '', 'error');
					exit;
				}elseif($_GET['openid'] == $_W['openid']){
					message('不可以代取自己的包裹。', '', 'error');
					exit;
				}
				
				$result	= pdo_fetch("SELECT * FROM " . tablename('ewei_express') . " WHERE `id` = '".$pid."'");
				$url = urlencode('http://weixin./app/index.php?i=6&c=entry&do=PackageInfo&tid='.$tid.'&m=ewei_express&sign='.base64_encode($pid.'-'.time()));
				
				include $this->template('Transfer_Receive');
				exit;
			}else{
				//先验证是不是本人的，不然就是非法访问
				$mobile_str = $this->getMobileList();
				$result	= pdo_fetch("SELECT * FROM " . tablename('ewei_express') . " WHERE `id` = '".$pid."' and `mobile` in (" .$mobile_str .")");
				if($result){
					$f_time1 = time(); 
					$f_appid = ''; //appid
					$f_appkey = '';// 秘钥
					$res = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$f_appid."&secret=".$f_appkey);// 获得token
					$ress = json_decode($res,True);
					$token = $ress['access_token'];// 取出 
					$js = file_get_contents("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$token."&type=jsapi");
					$jss = json_decode($js,True);
					$jsapi_ticket = $jss['ticket'];//   取出JS凭证
					$url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
					
					$string = "jsapi_ticket=$jsapi_ticket&noncestr=sjijfdifdfb&timestamp=$f_time1&url=$url";

					$f_signature = sha1($string);
			
					include $this->template('Transfer');
				}else{
					message('该包裹未开启代取或原主人已经取消代取。', '', 'error');
				}
			}
		}else{
			message('非法访问-4', '', 'error');
		}
		
	}
	
	public function doMobileDelivery() { //app用户配送信息
		//TODO：到付件不允许提交
		global $_W, $_GPC;
		$teacher = pdo_fetch('SELECT openid FROM ' . tablename('ewei_express_teacher') . ' WHERE `openid` = "' . $_W['openid'] . '"');//查是否是教师
		
		if($_POST){
			if($teacher){//如果是老师，根据地址信息查楼栋号是否正确
				$address = pdo_fetch('SELECT building FROM ' . tablename('ewei_express_address') . ' WHERE `id` = "' . intval($_POST['address']) . '"');//查是否是教师
				if($address['building'] == 7 || $address['building'] == 9 || $address['building'] == 10){
					message('教师订单不允许配送至学生楼栋，请返回修改。', '', 'error');
					exit;
				}
			}
			$arr = array();
			$arr['pid'] = intval($_GET['pid']);
			$arr['status'] = '等待称重计费';
			$arr['address'] = intval($_POST['address']);
			$arr['deliverytime'] = trim($_POST['deliverytime']);
			$result = pdo_insert('ewei_express_delivery', $arr);
			pdo_update('ewei_express', array('status' => 1), array('id' => $arr['pid']));
			message('送货上门申请提交成功，请等待工作人员称重计费。', $this->createMobileUrl('index',array('weid' => $row[weid], 'id' => $reid, 'status'=>1)), 'success');
		}else{
			$this->getTransfer(intval($_GET['pid']));//判断状态是否代取中
			$lists = pdo_fetchall('SELECT * FROM ' . tablename('ewei_express_address') . ' WHERE `openid` = "' . $_W['openid'] . '"');	
			include $this->template('Delivery');	
		}
	}
	
	public function doMobilePay() {
        global $_W, $_GPC;
        if (empty($_W['openid'])) {
            checkauth();
        }
		$pid = intval($_GPC['pid']);
		$r = $this->getDelivery($pid);
		
        $params['tid']     = $pid;
        $params['user']    = $_W['openid'];
        $params['fee']     = $r['price'];
        $params['title']   = '建工裹裹物流配送服务*'.$r['code'].' 计费重量:'.$r['weight'].'kg';
        $params['ordersn'] = $pid;
        $params['virtual'] = 1;		
		
		include $this->template('Pay');
	}
	
    public function payResult($params)
    {
        if($params['result'] == 'success'){
			pdo_update('ewei_express_delivery', array('status' => '送货中' , 'paytype' => $params['type'] , 'transid' => $params['tag']['transaction_id'] , 'paytime'=>date('Y-m-d H:i:s')), array('pid' => $params['tid']));
			message('支付成功！', $this->createMobileUrl('index',array('weid' => $row[weid], 'id' => $reid, 'status'=>1)), 'success');
		}else{
			message('支付失败，请重试！','','error');
		}
    }
	
	public function doMobileEvaluation() { //app用户评价
		if($_POST){
			$arr = array();
			$arr['pid'] = intval($_GET['pid']);
			$arr['service'] = trim($_POST['service']);
			$arr['speed'] = trim($_POST['speed']);
			$arr['compliant'] = trim($_POST['compliant']);
			$result = pdo_insert('ewei_express_evaluation', $arr);
			if($result){
				pdo_update('ewei_express', array('evaluate' => 1), array('id' => $arr['pid']));
				message('评价成功！', $this->createMobileUrl('index',array('weid' => $row[weid], 'id' => $reid, 'status'=>2)), 'success');
			}else{
				message('评价失败，请重试！', $this->createMobileUrl('index',array('weid' => $row[weid], 'id' => $reid, 'status'=>2)), 'error');
			}
		}
        include $this->template('Evaluation');
	}
	
	public function doMobileCollectionAuth(){//app用户代收授权同意（from:推送消息）
		$r = pdo_update('ewei_express_delivery', array('collectionAuth' => '1'), array('pid' => $_GET['pid']));
		if($r){
			message('包裹代收授权成功！','','success');
		}else{
			message('包裹代收授权失败，请联系开发人员！','','error');
		}
	}
	
	public function doMobilePackageInfo() { //app工作人员自取快递信息
		//TODO:出库操作 $_GET['tid'] 代取的情况
		global $_W, $_GPC;
		$this->IsWorker($_W['openid']);
		$pid = intval($_POST['pid']);
		$mobile = trim($_POST['mobile']);
		
		if($pid != ''){
			pdo_update('ewei_express', array('status' => 2,'finishtime'=>date('Y-m-d H:i:s'),'worker'=>''), array('id' => $pid));
			pdo_update('ewei_express_delivery', array('status' => '已完成'), array('pid' => $pid));
			//TODO:记录出库人
			
			$openid = $this->getMobileOpenid($mobile);
			if($openid != ''){
				$data = array (
					'first' => array('value' => '您的快递已取货（配送）完成，点击进行评价！','color' => '#FF0000'),
					'keyword1' => array('value' => $_POST['express_company']),
					'keyword2' => array('value' => $_POST['express_sn']),
					'keyword3' => array('value' => date('Y-m-d H:i:s')),
					'keyword4' => array('value' => '十号楼101（原菜鸟驿站）'),
					'remark' => array('value' => '感谢您使用建工裹裹物流服务。','color' => '#008000'),
				);
				$acc = WeAccount::create($_W['acid']);
				$acc->sendTplNotice($openid,'nc78WS1DXsHv8nPKQQ7tPyhHQoOIr5VkzuU6ExX5kYs',$data,'http://weixin./app/index.php?i=6&c=entry&do=Evaluation&m=ewei_express&pid='.$pid);
			}
			message('出库成功！', $this->createMobileUrl('PackageSearch',array('weid' => $row[weid], 'id' => $reid), 'success'));
		}
		
		if($_GET['pid'] != ''){
			$sign[0] = intval($_GET['pid']);
			$sign[1] = time() + 1;
		}else{
			$sign = explode('-',base64_decode($_GET['sign']));
		}
		
		if($_GET['code'] != ''){
			$sign[1] = time() + 1;
		}
		
		if(time() - $sign[1] > 60){// >
			message('取货二维码已过期。', '', 'error');
		}else{
			$result	= pdo_fetch("SELECT * FROM " . tablename('ewei_express') . " WHERE `id` = '".$sign[0]."' or `code` = '".$_GET['code']."'");
			include $this->template('PackageInfo');
		}
	}
	
	public function doMobilePackageSearch() { //app工作人员快递查询
		global $_W, $_GPC;
		$this->IsWorker($_W['openid']);
		
		if($_POST){
			$code = trim($_POST['code']);
			$express_sn = trim($_POST['express_sn']);
			if($code != ''){
				$result	= pdo_fetch("SELECT id FROM " . tablename('ewei_express') . " WHERE `code` = '".$code."'");
			}elseif($express_sn != ''){
				$result	= pdo_fetch("SELECT id FROM " . tablename('ewei_express') . " WHERE `express_sn` = '".$express_sn."'");
			}
			
			if($result){
				header('Location:'.$this->createMobileUrl('PackageInfo',array('weid' => $row[weid], 'pid' => $result['id'])));
				exit;
			}else{
				message('未找到相应快递信息，请检查输入。', '', 'error');
			}
		}else{
			include $this->template('PackageSearch');
		}
	}
	
	public function doMobileTaskList() { //app工作人员派送任务列表
		global $_W, $_GPC;
		$this->IsWorker($_W['openid']);
		$action = $_GPC['action'];
		if($action == ''){
			//js-sdk
			$f_time1 = time(); 
			$f_appid = ''; //appid
			$f_appkey = '';// 秘钥
			$res = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$f_appid."&secret=".$f_appkey);// 获得token
			$ress = json_decode($res,True);
			$token = $ress['access_token'];// 取出 
			$js = file_get_contents("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$token."&type=jsapi");
			$jss = json_decode($js,True);
			$jsapi_ticket = $jss['ticket'];//   取出JS凭证
			$url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			
			$string = "jsapi_ticket=$jsapi_ticket&noncestr=sjijfdifdfb&timestamp=$f_time1&url=$url";

			$f_signature = sha1($string);
					
			$status = empty($_GPC['status']) ? '送货中' : trim($_GPC['status']);
			$lists = pdo_fetchall('SELECT * FROM view_express_deliverylist WHERE `status` = "' . $status . '" order by building,id desc');
			include $this->template('TaskList');
		}elseif($action == 'changeDelivery'){
			$r = pdo_fetch("SELECT * FROM view_express_deliverylist where id=".$_GPC['id']);
			if($r['changedelivery'] == date('Y-m-d')){
				exit('操作失败，原因：今日已操作');
			}else{
				pdo_update('ewei_express_delivery', array('changedelivery' => date('Y-m-d')), array('id' => $_GPC['id']));
				$openid = $this->getMobileOpenid($r['mobile']);
				if($openid != ''){
					$data = array (
						'first' => array('value' => '您的包裹'.$r['express_company'].'*'.$r['code'].'发生配送异常，请注意','color' => '#FF0000'),
						'keyword1' => array('value' => $_GPC['id']),
						'keyword2' => array('value' => $r['price']),
						'keyword3' => array('value' => $r['building'].'-'.$r['room']),
						'keyword4' => array('value' => '由于无法联系收件人或该地址无人代收，配送人员已经标记该订单为明日再送。'),
						'remark' => array('value' => '感谢您使用建工裹裹物流服务。','color' => '#008000'),
					);
					$acc = WeAccount::create($_W['acid']);
					$acc->sendTplNotice($openid,'ADeTUY4ABhZYxseBsZH79cgv8r8lMmNivF_fXs4n7Eo',$data);
					exit('操作成功');
				}
			}	
		}elseif($action == 'collectionAuth'){
			$r = pdo_fetch("SELECT * FROM view_express_deliverylist where id=".$_GPC['id']);
			$openid = $this->getMobileOpenid($r['mobile']);
			if(!$r['collectionAuth']){
				if($openid != ''){
					$data = array (
						'first' => array('value' => '您的包裹'.$r['express_company'].'*'.$r['code'].'正在配送，由于您不在或无法联系，配送人员请求您授权他人代收。','color' => '#FF0000'),
						'keyword1' => array('value' => $_GPC['id']),
						'keyword2' => array('value' => '送货上门'),
						'remark' => array('value' => '如同意代收，请点击“详情”；如不同意，请忽略本消息。','color' => '#008000'),
					);
					$acc = WeAccount::create($_W['acid']);
					$acc->sendTplNotice($openid,'uni-ZygHra6zk7_nb5jwiipSUUBpeMevrk0BwyCOr-k',$data,'http://weixin./app/index.php?i=6&c=entry&do=CollectionAuth&m=ewei_express&pid='.$r['pid']);
					exit('代收授权发起成功，请等待用户进行下一步操作。');
				}
			}else{
				exit('用户已授权，点击确定跳转到出库界面。');
			}

		}elseif($action == 'scanQRCode'){
			$r = pdo_fetch("SELECT * FROM view_express_deliverylist where id=".$_GPC['id']);
			$openid = $this->getMobileOpenid($r['mobile']);
			if($openid != ''){
				$data = array (
					'first' => array('value' => '您的包裹'.$r['express_company'].'*'.$r['code'].'正在配送，请点击“详情”并向配送人员出示二维码完成配送。','color' => '#FF0000'),
					'keyword1' => array('value' => $_GPC['id']),
					'keyword2' => array('value' => '送货上门'),
					'remark' => array('value' => '感谢您使用建工裹裹物流服务。','color' => '#008000'),
				);
				$acc = WeAccount::create($_W['acid']);
				$acc->sendTplNotice($openid,'uni-ZygHra6zk7_nb5jwiipSUUBpeMevrk0BwyCOr-k',$data,'http://weixin./app/index.php?i=6&c=entry&do=SelfPick&m=ewei_express&pid='.$r['pid']);
			}
		}
	}	
	
	public function doMobileDNKXPush() { //逗你开心接口推送		

		if($_REQUEST){
			if($_REQUEST['token'] != md5('DNKX'.date('Ymd'))){
				echo json_encode(array('status'=>'illegal'));
			}else{
				$msg['type'] = $_REQUEST['type'];
				$msg['code'] = $_REQUEST['code'];
				$msg['mobile'] = $_REQUEST['tel'];
				$msg['express_company'] = $_REQUEST['expressName'];
				$msg['express_sn'] = $_REQUEST['waybillNo'];				
				// $msg['bz'] = json_encode($_REQUEST);
				if($msg['type'] == '1'){
					$result = pdo_insert('ewei_express', $msg);
				}
				if (!empty($result) && $msg['type'] != '2') {
					//模版消息推送
					$openid = $this->getMobileOpenid($msg['mobile']);
					if($openid != ''){
						$data = array (
							'first' => array('value' => '您有新的包裹到了，赶快来取吧！','color' => '#FF0000'),
							'keyword1' => array('value' => $msg['express_company']),
							'keyword2' => array('value' => $msg['express_sn']),
							'keyword3' => array('value' => date('Y-m-d').' 12:00以后'),
							'keyword4' => array('value' => '十号楼101（原菜鸟驿站）'),
							'remark' => array('value' => '感谢您使用建工裹裹物流服务。','color' => '#008000'),
						);
						$acc = WeAccount::create($_W['acid']);
						$acc->sendTplNotice($openid,'nc78WS1DXsHv8nPKQQ7tPyhHQoOIr5VkzuU6ExX5kYs',$data,'http://weixin./app/index.php?i=6&c=entry&do=index&m=ewei_express');
					}
					//短信推送
					send_msg($msg['mobile'], '您的包裹'.$msg['express_company'].'*'.$msg['code'].'到了，请关注"新锐空间"微信公众号点击"建工裹裹"，凭取货二维码到10号楼101室取件。现在关注即可享受3次免费送货上门服务【新锐空间】');
					echo json_encode(array('status'=>'success'));
				}else{
					echo json_encode(array('status'=>'failed'));
				}
			}
		}else{
			echo json_encode(array('status'=>'illegal'));
		}
	}
	
	public function getMobileOpenid($mobile){
		$r = pdo_fetch("SELECT f.openid FROM `ims_mc_mapping_fans` as f,`ims_mc_members` as m where m.mobile='$mobile' and m.uid=f.uid;");
		if(!empty($r)){
			return $r['openid'];
		}else{
			$r = pdo_fetch('SELECT openid FROM ' . tablename('ewei_express_phonenum') . ' WHERE `phonenum` = "' . $mobile . '"');
			return $r['openid'];
		}
		
	}
	
	public function getMobileList(){//取手机号列表
		global $_W, $_GPC;
		
		$mobile = array($_W['member']['mobile']);//自己的手机+绑定的
		
		$lists = pdo_fetchall('SELECT phonenum FROM ' . tablename('ewei_express_phonenum') . ' WHERE `openid` = "' . $_W['openid'] . '"');
		foreach ($lists as $list) {
			$mobile[] = $list['phonenum'];  
		}
		return join(',', $mobile);//文本化手机号数组
	}
	
	public function getTransfer($pid){//检查是否代取中
		$result = pdo_fetch('SELECT transfer FROM ' . tablename('ewei_express') . ' WHERE `id` = "' . $pid . '"');
		if($result['transfer']){
			message('包裹正在代取中，无法操作。', '', 'error');
			exit;
		}
	}
	
	public function getDelivery($pid){//检查是否代取中
		$result = pdo_fetch('SELECT * FROM view_express_deliverylist WHERE `pid` = "' . $pid . '"');
		return $result;
	}
	
	public function getEvaluation($pid){//取评价
		$result = pdo_fetch('SELECT * FROM ' . tablename('ewei_express_evaluation') . ' WHERE `pid` = "' . $pid . '"');
		return '服务：'.$result['service'].'\r\n速度：'.$result['speed'].'\r\n投诉建议：'.$result['compliant'].'\r\n评价时间：'.$result['ctm'];
	}
	
	public function IsWorker($openid){//检查是否工作人员
		$result = pdo_fetch('SELECT * FROM ' . tablename('ewei_express_employee') . ' WHERE `e_openid` = "' . $openid . '"');
		if($result){
			return true;
		}else{
			message('不是工作人员，无权访问。', '', 'error');
			exit;
		}
	}
}
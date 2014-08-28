<?php


define("TOKEN","weixin");
if(true){
	require_once './mysql_sae.func.php';
    require_once("./simple_html_dom.php");
}

$wechatObj = new wechatCallbackapiTest();
if(isset($_GET['echostr'])){
	$wechatObj->valid();
}else{
	$wechatObj->responseMsg2();
}

class wechatCallbackapiTest
{
	public function valid()
	{
		$echostr = $_GET['echostr'];
		if($this->checkSignature()){
			echo $echostr;
			exit;
		}
	}
	
	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		
		$token = TOKEN;
		$tmpArr = array($token,$timestamp,$nonce);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		
		if($tmpStr == $signature){
			return true;
		}else{
			return false;
		}
		
	}
	
	public function responseMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		
		if(!empty($postStr)){
			$postObj = simplexml_load_string($postStr,"SimpleXMLElement",LIBXML_NOCDATA);
			$fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
			
			$textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
			if($keyword=="?" || $keyword=="��"){
				$msgType = "text";
				$contentStr = "С�������뷢������ָ�\n1����ѯ������Ϣ������+������������+����\n2����ѯΥ����Ϣ��Υ��+���ƺ�+����������λ����Υ��+N12345+12345\n3���󶨳�����Ϣ����+���ƺ�+����������λ�����+N12345+12345\n4�������Ϣ�����+���ƺţ�����+N12345\n";
            	$contentStr .="ע�⣺�󶨳�����Ϣ֮��С���ᶨ��Ϊ����ѯΥ����Ϣ��������Υ�£�С�����һʱ��֪ͨ����������˳�����Ϣ���û�����ֱ�����룺Υ�£���ѯΥ����Ϣ��";
				$resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,$msgType,$contentStr);
				echo $resultStr;
			}else{
				$msgType = "text";
				$contentStr = "��ʱ��֧��[".$keyword."]ָ������룿��ѯ�������ָ�";
				$resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,$msgType,$contentStr);
				echo $resultStr;
			}
		}else{
			echo "";
			exit;
		}
	}
	
	public function responseMsg2()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
	}
	
	//�����¼����͵�����
	private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "��ӭ��ע������·�����Ĺ����ʺš�\nС��ÿ�»���������Υ����Ϣ���ó�֪ʶ���ͼ���Ϣ��\n���幦���뷢������ָ�\n1����ѯ������Ϣ������+������������+����\n2����ѯΥ����Ϣ��Υ��+���ƺ�+����������λ����Υ��+N12345+12345\n3���󶨳�����Ϣ����+���ƺ�+����������λ�����+N12345+12345\n4�������Ϣ�����+���ƺţ�����+N12345\n";
            	$content .="ע�⣺�󶨳�����Ϣ֮��С���ᶨ��Ϊ����ѯΥ����Ϣ��������Υ�£�С�����һʱ��֪ͨ����������˳�����Ϣ���û�����ֱ�����룺Υ�£���ѯΥ����Ϣ��";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }
	
	//����Text���͵�����
	private function receiveText($object)
    {
        $resultStr = "";
        $keyword = trim($object->Content);
		if($keyword=="?" || $keyword=="��" || $keyword=="help"){
			$resultStr = $this->help($object);
            //return $helpResult;
		}else{
			$cmdArr = explode("+",$keyword);
			if(trim($cmdArr[0] == 'Υ��')){
                if(count($cmdArr)==3){
					//��������ĸ�ʽΪ��Υ��+aaaaaa+12345����ֱ�Ӳ��ң���������ݿ���в�ѯ
					//�ȼ���²�����ʽ
					$paramCheckobj = new ParamCheckUtil();
					$checkResultArray = $paramCheckobj->trafficViolationParamCheck($cmdArr[0], $cmdArr[1], $cmdArr[2]);
					if((is_array($checkResultArray)==1) && (count($checkResultArray)==4) && ($checkResultArray[0]==true)){
						$tvObj = new trafficViolationApi();
						$originDataStrzxc = $tvObj->getHHData($checkResultArray[2], $checkResultArray[3]);
						$originDataArr = $tvObj->prepareDatatmp($originDataStrzxc,$checkResultArray[2], $checkResultArray[3]);
						$resultStr = $this->transmitNew($object, $originDataArr);
					}else{
						$contentStr = "�����ʽ������ȷ�ĸ�ʽΪ��Υ��+aaaaaa+12345";
						$resultStr = $this->transmitText($object, $contentStr);
					}
				}else if(count($cmdArr)==1){
					//���ֻ����:Υ�£����Դ����ݿ���س��ơ��������������Ϣ
                    $flag = $this->getBindingNum($object);
                    if($flag == 1){
						$bindingInfoArr = $this->getBindingInfo($object);
						if(is_array($bindingInfoArr) && count($bindingInfoArr)>0){
							$plateNumber = $bindingInfoArr['plate_num'];
							$engineNumber = $bindingInfoArr['engine_num'];
							$tvObj = new trafficViolationApi();
							$originDataStrzxc = $tvObj->getHHData($plateNumber, $engineNumber);
							$originDataArr = $tvObj->prepareDatatmp($originDataStrzxc,$plateNumber, $engineNumber);
							$resultStr = $this->transmitNew($object, $originDataArr);
                        }else{
						//û�а���Ϣ
						$contentStr = "��ǰ�û���û�а󶨳��ƣ����Ȱ���Ϣ�������롰Υ��+aaaaaa+12345��ָ��˺��복�ƽ��а�";
                        $resultStr = $this->transmitText($object, $contentStr);
                        }
                    }elseif($flag > 1){
                        $contentStr = $this->autoBindingInfo($object);
                
                        $resultStr = $this->transmitNews($object, $contentStr);
                     
                    }else{
                        //û�а���Ϣ
						$contentStr = "��ǰ�û���û�а󶨳��ƣ����Ȱ���Ϣ�������롰Υ��+aaaaaa+12345��ָ��˺��복�ƽ��а�";
                        $resultStr = $this->transmitText($object, $contentStr);
                    }
				}else{
					//ָ�����
					$contentStr = "ָ���������������룬��������?��ѯϵͳ֧�ֵ�ָ��";
                    $resultStr = $this->transmitText($object, $contentStr);
				}
			}else if(trim($cmdArr[0] == '����')){
				//����������ѯ
                $url = "http://apix.sinaapp.com/weather/?appkey=".$object->ToUserName."&city=".urlencode($cmdArr[1]); 
                $output = file_get_contents($url);
                $content = json_decode($output, true);
                if(is_array($content)!=1 || count($content)<=0){
                    $contentStr = "û�г��С�".$keyword."����������Ϣ����ȷ�ϸó��������Ƿ�ƴд��ȷ�����������������ѯ��\n���������ʺ�?����ѯϵͳ�����ṩ�Ĺ��ܡ�";
                    $resultStr = $this->transmitText($object, $contentStr);
                }else{
                    $resultStr = $this->transmitNews($object, $content);
                }
                //return $resultStr;
            }else if(trim($cmdArr[0] == '��')){
                $contentStr = $this->binding($object, $cmdArr);
                    
                $resultStr = $this->transmitText($object, $contentStr);
                
            }else if(trim($cmdArr[0] == '���')){
                $contentStr = $this->unbinding($object, $cmdArr);
                
                $resultStr = $this->transmitText($object, $contentStr);
                
            }else if(trim($cmdArr[0] == '��ѯ��')){
                $contentStr = $this->searchbinding($object);
                
                $resultStr = $this->transmitText($object, $contentStr);
                
            }else if(trim($cmdArr[0] == '����')){
                    //���Զ���û�Υ�²�ѯ
                //$contentStr = array();
                //$contentStr = $this->autoBindingInfo($object);
                
                //$resultStr = $this->transmitText($object, $contentStr);
                //$resultStr = $this->transmitNews($object, $contentStr);
                
                
            }else{
            	$contentStr = "��ʱ��֧��[".$keyword."]ָ�\n��ȷ��ָ���ǡ�����+����������Υ�²�ѯ���룺Υ��+A1234B+12345��������ѯ������+������";
				$resultStr = $this->transmitText($object, $contentStr);
                //return $resultStr;
            }
		}
        return $resultStr;
		
    }
	
	//�򵥵İ�������������ʾ�û�������
	private function help($object)
	{
		$content = "С�������뷢������ָ�\n1����ѯ������Ϣ������+������������+����\n2����ѯΥ����Ϣ��Υ��+���ƺ�+����������λ����Υ��+N12345+12345\n3���󶨳�����Ϣ����+���ƺ�+����������λ�����+N12345+12345\n4�������Ϣ�����+���ƺţ�����+N12345\n";
        $content .="ע�⣺�󶨳�����Ϣ֮��С���ᶨ��Ϊ����ѯΥ����Ϣ��������Υ�£�С�����һʱ��֪ͨ����������˳�����Ϣ���û�����ֱ�����룺Υ�£���ѯΥ����Ϣ��";
		return $this->transmitText($object,$content);
	}
	
	//���ͼ�text���͵���Ϣ
	private function transmitText($object, $content)
    {
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }
	
	//����ͼ����Ϣ(����)
	private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return;
        }
        $itemTpl = "<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>
					%s</Articles>
					</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray), $item_str);
        return $result;
    }
	
	//����ͼ����Ϣ(����)
	private function transmitNew($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return;
        }
        $itemTpl = "<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>";
        $item_str = "";
        
        $item_str = sprintf($itemTpl, $newsArray['Title'], $newsArray['Description'], $newsArray['PicUrl'], $newsArray['Url']);
        
        $newsTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>
					%s</Articles>
					</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), 1, $item_str);
        return $result;
    }
    
    //�ж��˺��Ƿ��
    private function isBinding($object, $cmdArr)
    {
        //�ж��Ƿ��Ѿ���
		$select_sql = "SELECT id from users WHERE plate_num='$cmdArr[1]' and from_user='$object->FromUserName'";
		$res = _select_data($select_sql);
		$rows = mysql_fetch_array($res, MYSQL_ASSOC);
		if($rows[id] <> ''){
        	$user_flag = 'y';  
        }else{
            $user_flag = 'n';
        }
        return $user_flag;
    }
    
    //�ж�΢�źŰ󶨳��ƺ���
    private function getBindingNum($object)
    {
        //�ж��Ƿ��Ѿ���
		$select_sql = "SELECT id from users WHERE from_user='$object->FromUserName'";
		$res = _select_data($select_sql);
        $num = 0;
        while($rows = mysql_fetch_array($res, MYSQL_ASSOC)){
            $num = $num + 1;
        }
        return $num;
    }
	
	//��ȡ�˺ŵİ���Ϣ
	private function getBindingInfo($object)
	{
		//�ж��Ƿ��Ѿ���
		$select_sql = "SELECT * from users WHERE from_user='$object->FromUserName'";
		$res = _select_data($select_sql);
		//������ȡ��һ�����ݣ������Ҫ��ȡ�����У�Ӧ��ѭ��ִ��mysql_fetch_array���
		$rows = mysql_fetch_array($res, MYSQL_ASSOC);
		
        return $rows;
	}
    
    //��ȡ���˻���Ϣ
    private function autoBindingInfo($object)
    {
        //
        $select_sql = "SELECT * from users WHERE from_user = '$object->FromUserName'";
		$res = _select_data($select_sql);
        //return $res;
        //$rows = mysql_fetch_array($res, MYSQL_ASSOC);
        $num = 1;
        //$arrayarray = array();
        $InfoArr = array();
        $InfoArr[1] = array (
			'Title'=>"����Υ����Ϣ",
			'Description'=>"",
			'PicUrl'=>'',
            'Url'=>''
		);
        while($rows = mysql_fetch_array($res, MYSQL_ASSOC)){
            $num = $num + 1;
            $plateNumber = $rows['plate_num'];
            $engineNumber = $rows['engine_num'];
            $listObj = new trafficViolationApi();
            //$originContent = $listObj->getHHData($plateNumber, $engineNumber);
            //$InfoArr[$num] = $listObj->prepareDatatmp($originContent, $plateNumber, $engineNumber);
            
            $valueArray = $listObj->getValueData($plateNumber, $engineNumber);
            
            $instoreflag = $listObj->checkDatatmp($valueArray, $plateNumber, $engineNumber);		//��Υ����Ϣ�������ݿ�
            
            
            $flagInfo = $listObj->updateDatatmp($valueArray, $plateNumber);
            
            
            if($flagInfo >0){
                $InfoArr[$num] = $listObj->getMultieDatatmp($plateNumber,$engineNumber);
            }else{
                $InfoArr[$num] = "δ�鵽�����Ϣ��";
            }
        }
        
        

        return $InfoArr;
        //return $instoreflag;
    }
    
    //���û�
    private function binding($object, $cmdArr)
    {
        $user_flag = $this->isBinding($object, $cmdArr);
        $nowtime=date("Y-m-d G:i:s");
        //$fromuser = $object->FromUserName;
        if($user_flag <> 'y'){
            $insert_sql="INSERT INTO users(from_user, plate_num, engine_num, update_time) VALUES('$object->FromUserName','$cmdArr[1]','$cmdArr[2]','$nowtime')";
        	$res = _insert_data($insert_sql);
        	if($res == 1){
                $ret = "�󶨳ɹ�";
        	}elseif($res == 0){
            	$ret = "��ʧ��";
            }
        }else{
            $ret = "���û��Ѱ�";
        }
        return $ret;
    }
    
    //����û�
    private function unbinding($object, $cmdArr)
    {
        $user_flag = $this->isBinding($object, $cmdArr);
        if($user_flag<>'n'){
            $delete_sql = "delete from users where plate_num = '$cmdArr[1]'";
            $result = _delete_data($delete_sql);
			if($result == 1){
    			$ret = "�ó����ѽ����";
			}elseif($result == 0){
                $ret = "���ʧ��";
			}elseif($result == 2){
   				$ret = "û�иó��ư�";
            }
        }else{
            $ret = "���û�δ��";
        }
        return $ret;
    }
    
    //��ѯ�����
    private function searchbinding($object)
    {
        $select_sql = "SELECT * from users WHERE from_user = '$object->FromUserName'";
		$res = _select_data($select_sql);
        //return $res;
        //$rows = mysql_fetch_array($res, MYSQL_ASSOC);
        $num = 0;
        $ret ="���󶨵ĳ��������¼�����\n";
        while($rows = mysql_fetch_array($res, MYSQL_ASSOC)){
            $num = $num + 1;
            $ret .= $num;
            $ret .= "��";
            $ret .= $rows['plate_num'];
            $ret .= "\n";
            //$ret = $num + ".  " + $rows['plate_num'] + '\n';
        }
        return $ret;
    }    
	
	//��־��¼
	private function logger($log_content)
    {
    
    }
	
}

class trafficViolationApi
{
	public function getHHData($plateNumber,$engineNumber){
		$uri = "http://so.jtjc.cn/pl";
		// ��������
		$data = array (
				'Fzjg' => 'N',
				'Webform' => 'jtjc.cn',
				'WebSite' => 'Index',
				'd' => '02',
				't' => '��',
				'p' => $plateNumber,
				'cjh' => $engineNumber,
				'btnG' => 'Υ����ѯ'
		);
		 
		$ch = curl_init ();
		// print_r($ch);
		curl_setopt ( $ch, CURLOPT_URL, $uri );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		$return = curl_exec ( $ch );
		curl_close ( $ch );
		 
		preg_match_all("/<li class='address'><div class='item'><b>(.*?)<\/b><span>(.*?)(<\/span>)*<\/div><\/li>/", $return, $title);
		 
		if(count($title)==4){
			$textArray = $title[1];
			$valueArray = $title[2];

			$textCount = count($textArray);
			$valueCount = count($valueArray);
			$curCount = ($textCount>$valueCount)?$valueCount:$textCount;
            if($curCount<=0){
            	$resultStr = "��ϲ����û���κ�Υ����Ϣ��";
            }
			//��ȡҳ���ϵĴ�����ʾ��Ϣ
			if($textCount==0 && $valueCount==0){
                $dom_tmp = str_get_dom($return);
                $msg = $dom_tmp->find('div[class=spx]', 0);
                if(count($msg)==1){
					$resultStr = trim($msg->plaintext);
					
				}else{
					$resultStr = "��ϲ����û���κ�Υ����Ϣ��";
				}
            }else{
				$resultStrArr = "";
				for($i=0;$i<$curCount;$i++){
					$resultStrArr[$i/10] .= ($textArray[$i].$valueArray[$i]." \n");
				}
				//���ݹ���Ļ���ֻ���ص�һ���������ķŵ�����ҳ��
				/*foreach($resultStrArr as $curResultStr){
					$resultStr .= $curResultStr."\n";
				}*/
				$resultStr = $resultStrArr[0];
			}
			
		}else{
			$resultStr = "��ȡ��Ϣʧ��";
		}
		
		return $resultStr;
	}
	
	public function prepareDatatmp($content,$plateNumber,$engineNumber){
        $detailUrl = "http://whucsers.sinaapp.com/violationInfo.php?plateNumber=".$plateNumber."&engineNumber=".$engineNumber;
		$dataArr = array (
			'Title'=>'Υ����Ϣ��ѯ',
			'Description'=>$content,
			'PicUrl'=>'',
            'Url'=>$detailUrl
		);
		return $dataArr; 
	}
    
    //��ȡvalueArray����
    public function getValueData($plateNumber,$engineNumber){
        $uri = "http://so.jtjc.cn/pl";
		// ��������
		$data = array (
				'Fzjg' => 'N',
				'Webform' => 'jtjc.cn',
				'WebSite' => 'Index',
				'd' => '02',
				't' => '��',
				'p' => $plateNumber,
				'cjh' => $engineNumber,
				'btnG' => 'Υ����ѯ'
		);
		 
		$ch = curl_init ();
		// print_r($ch);
		curl_setopt ( $ch, CURLOPT_URL, $uri );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
		$return = curl_exec ( $ch );
		curl_close ( $ch );
		 
		preg_match_all("/<li class='address'><div class='item'><b>(.*?)<\/b><span>(.*?)(<\/span>)*<\/div><\/li>/", $return, $title);
        
        if(count($title)==4){
            $valueArray = $title[2];
        }else{
            $valueArray = "��Υ����Ϣ";
        }
        return $valueArray;
    }
    
    //�洢Υ����Ϣ��peccancyInfo��
    public function checkDatatmp($valueArray, $plate_num, $engine_num){
        
        $valueNum = count($valueArray);
        $nowtime = date("Y-m-d G:i:s");
        for($i=0; $i<($valueNum/10); $i++){
            //$plate_num = $valueArray[0];
            $office_a = $valueArray[$i*10];
            $place_a = $valueArray[$i*10+1];
       		$actioncode_a = $valueArray[$i*10+2];
            $point_a = $valueArray[$i*10+3];
            $money_a = $valueArray[$i*10+4];
            $action_a = $valueArray[$i*10+5];
            $law_a = $valueArray[$i*10+6];
        	$occurtime_a = $valueArray[$i*10+7];
       		$flag_a = $valueArray[$i*10+8];
            $origin_a = $valueArray[$i*10+9];

        	//�ж�Υ����Ϣ�Ƿ񴢴棬���ó��ƺţ�Υ����Ϊ���룬Υ��ʱ����ֵȷ��
            
            $rows = 0;
        	$select_sql = "select * from peccancyInfo where plate_num='$plate_num' and actioncode='$actioncode_a' and occurtime='$occurtime_a'";

			$result = _select_data($select_sql);
        
        	if($rows = mysql_fetch_array($result,MYSQL_ASSOC)){
            	//������Υ���Ƿ�ɷ���Ϣ
            	$ret = 1;  //Υ�������Ѵ������ݿ�
        	}else{
            	//��������
            	$insert_sql = "insert into peccancyInfo(plate_num, engine_num, office, place, actioncode, point, money, action, law, occurtime, flag, origin, update_time) values('$plate_num', '$engine_num', '$office_a','$place_a','$actioncode_a','$point_a','$money_a','$action_a', '$law_a','$occurtime_a','$flag_a','$origin_a', '$nowtime')";

				$res = _insert_data($insert_sql);
            	if($res == 1){
                	$ret = 2;  //����ɹ�
				}else{
                	$ret = 0;  //����ʧ��
            	}
        	}
        }
        return $ret;
    }
    
    //���㲢�洢Υ�¸�Ҫ��peccancyBerif��
    public function updateDatatmp($valueArray, $plate_num){
        
        //$allitems = 0;
        
        $allpoint = 0;
        
        $allmoney = 0;
        
        $valueCount = count($valueArray);
        
        $items = $valueCount/10;   //��������
        
        $nowtime = date("Y-m-d G:i:s");
        

        
        for($i=0; $i<=($valueCount/10); $i++){

            $allpoint = $allpoint + $valueArray[$i*10+3];		//�����۷�
            $allmoney = $allmoney + $valueArray[$i*10+4];       //�������
            //$allitems = $allitems + 1;						//��������
        }
        
             
        
        $select_sql = "select * from peccancyBerif where plate_num = '$plate_num'";

		$result = _select_data($select_sql);
        
        if($rows = mysql_fetch_array($result,MYSQL_ASSOC)){
            
        	//��������
			$update_sql = "update peccancyBerif set all_items='$items',all_point='$allpoint',all_money='$allmoney' where plate_num='$plate_num'";

			$res = _update_data($update_sql);
			if($res == 1){
                return 1;    //���³ɹ�
			}elseif($res == 0){
                return 0;    //����ʧ��
			}elseif($res == 2){
                return 2;   //û�����ܵ�Ӱ��
			}

        }else{
            $insert_sql = "insert into peccancyBerif(plate_num, all_items, all_point, all_money, update_time) values('$plate_num', '$items', '$allpoint', '$allmoney', '$nowtime')";

			$res = _insert_data($insert_sql);
			if($res == 1){
                return 3;   //����ɹ�
			}else{

                return -1;   //����ʧ��
			}
        }
        
    }
    
    //�ϳ�Υ�¸�Ҫ��Ϣ
    public function getMultieDatatmp($plateNumber,$engineNumber){
        
        $select_sql = "select * from peccancyBerif where plate_num='$plateNumber'";

		$result = _select_data($select_sql);
        
        $berif = "";
        
        if($rows = mysql_fetch_array($result,MYSQL_ASSOC)){
            $berif .= "���ƺţ���";
            $berif .= $plateNumber;
            $berif .= "\n��δ����Υ��";
            $berif .= $rows['all_items'];
            $berif .= "����۷�";
            $berif .= $rows['all_point'];
            $berif .= "�֣�������";
            $berif .= $rows['all_money'];
            $berif .= "Ԫ��";
            $berif .="\n����鿴���顣";
        }else{
            $berif .= "���ƺţ���";
            $berif .= $plateNumber;
            $berif .= "\n��Υ�¼�¼��";
        }
        
        $detailUrl = "http://whucsers.sinaapp.com/violationInfo.php?plateNumber=".$plateNumber."&engineNumber=".$engineNumber;
		$dataArr = array (
			'Title'=>$berif,
			'Description'=>"",
			'PicUrl'=>'',
            'Url'=>$detailUrl
		);
		return $dataArr; 
	}
        
        
}


class ParamCheckUtil
{
	//�����������ĸ�ʽ����ʽ����ΪΥ��+aaaaaa+12345�������ʽ��ȷ���򷵻��������ĸ�Ԫ�ص����飬�׸�Ԫ�ر����true����false����ʾ�������Ľ��
	public function trafficViolationParamCheck($cmd,$plateNumber,$engineNumber){
		$finalParams = array();
		$checkFlag = true;//��ǲ������Ľ��
		$finalCmd = trim($cmd);
		$finalPlateNumber = trim($plateNumber);
		$finalEngineNumber = trim($engineNumber);
		if(trim($cmd)=="Υ��"){
			$finalParams[1] = $finalCmd;
		}else{
			$checkFlag = false;
		}
		//���ƺ�
		$finalPlateNumberLength = strlen($finalPlateNumber);
		if($finalPlateNumberLength==6){
			$finalParams[2] = $finalPlateNumber;
		}else if(($finalPlateNumberLength==9) && strpos($finalPlateNumber,"��")==0){//���ĳ���Ϊ3
			$finalParams[3] = substr($finalPlateNumber,3);
		}else{
			$checkFlag = false;
		}
		//�����
		if(strlen($engineNumber)==5){
			$finalParams[3] = $finalEngineNumber;
		}else{
			$checkFlag = false;
		}
		//����ǵĽ����䵽����������
		$finalParams[0] = $checkFlag;
		
		return $finalParams;
	}
	
}
?>
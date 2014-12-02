<?php
/**
[uploadx php批量上传组件v2]
author ：陶之11
website ：http://www.taoz11.com/archives/uploadx.html
update ：2014-01-22
version ：uploadx version 2.0

说明：
UPLOADX php批量上传组件遵循开源协议(GPL)，任何个人、组织可自由对本程序进行使用、二次开发等权力。
由此也将声明本人不对您个人、组织使用本程序所带来的商业利益及损失有干涉及负责，但请保留版权信息。
也欢迎对uploadx提出保贵的建议及意见，不胜感激。
本程序使用PHP程序编写，能更高效的批量处理PHP开发中的文件上传，图片处理、批量添加图片水印等问题。
在使用本程序前请详细阅读使用说明：http://www.taoz11.com/archives/uploadx.html
*/
class uploadx {
		var $form = 'uploadx';
		var $save = './';		
		var $size = '1024';
		var $type = 'gif,bmp,png,jpg,jpeg,swf,flv,mp3,wma,rar,zip,7z,doc,docx,ppt,pptx,xls,xlsx,txt,pdf';
		var $name = null;
		var $mini = null;
		var $mark = null;
		var $version = '2.0';

		public function is($type = true){
			foreach ($this->files() as $key => $val) {
				$file = $mini = null;
				$file = $this->saves($val['name'], $val['type'], $val['tmp_name'], $val['size'], $val['error']);
				$file['code'] || $file['path'] = rtrim($this->save,'/').'/'.$file['name'];
				$file['code'] || $file['mini'] = $this->mini($file['path']);		
				$file['code'] || $file['mark'] = $this->mark($file['path']);				
				$file['code'] && $file['error'] = $this->error($file['code']);
				$type ? $files[] = $file :  ($file['code'] || $files[] = $file);
			}
				return isset($files) ? $files : array();
		}
		private function files(){
			if(count($_FILES[$this->form])<1) return array();
			if(is_array($_FILES[$this->form]['name'])){
				for($i=0; $i<count($_FILES[$this->form]['name']); $i++) {
					if($_FILES[$this->form]['error'][$i]==4) continue;
					$files[] = array(
						'name'=>$_FILES[$this->form]['name'][$i],
						'type'=>$_FILES[$this->form]['type'][$i],
						'tmp_name'=>$_FILES[$this->form]['tmp_name'][$i],
						'error'=>$_FILES[$this->form]['error'][$i],
						'size'=>$_FILES[$this->form]['size'][$i]);
				}	
			}else{
					$files[] = $_FILES[$this->form]; 
			}
					return $files;
		}
		private function saves($name, $type, $temp, $size, $error){		
				if($error) return array('name'=>$name, 'code'=>$error);
				$prefix = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				if(!in_array($prefix, explode(',', strtolower($this->type)))){
					return array('name'=>$name, 'code'=>'-1');
				}
				if($size/1024>$this->size){
					return array('name'=>$name, 'code'=>'-2');
				}
				if(!is_dir($this->save)){
					if(!mkdir($this->save, 0777, TRUE)){
					return array('name'=>$name, 'code'=>'-3');
					}					
				}
				$filename = $this->name ? ($this->name=='auto' ? uniqid() : $this->name) : trim(basename($name,$prefix),'.');
				$savefile = trim($this->save,'/').'/'. $filename.'.'.$prefix;
				if(!@move_uploaded_file($temp,$savefile)){
					return array('name'=>$name, 'code'=>'-4');
				}
				 @chmod($savefile,0777);  
				return array('name'=>$filename.'.'.$prefix, 'code'=>0);
		}
		public function mini($file = null){
			if(!$file || !$this->mini) return false;
			if(!is_file($file)) return $this->error(-5,$file);
			list($width, $height, $extends) = explode(',', $this->mini);
			$types = array('gif','png','jpg','jpeg');
			$type = pathinfo($file, PATHINFO_EXTENSION);
			if(!in_array($type, $types)) return $this->error(-6);
			if(!is_file($file)) return $this->error(-5,$file); 
			$mini = $extends ? basename($file, $type).$extends.'.'.$type : trim(basename($file),'.');
			$image = imagecreatefromstring(file_get_contents($file));
			$imagex = imagesx($image);
			$imagey = imagesy($image);
			$scale = $width / $imagex;
			if($width>$imagex){
				$mini_width = $imagex;
				$mini_height = $imagey; 
			}else{
				$mini_width = $width;
				$mini_height = round($scale * $imagey); 
			}
			if(function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')){
				$temp = imagecreatetruecolor($mini_width, $mini_height);
				imagecopyresampled($temp,$image,0,0,0,0,$mini_width, $mini_height, $imagex, $imagey);
			}else{
				$temp = imagecreate($mini_width, $mini_height);
				imagecopyresized($temp,$image,0,0,0,0,$mini_width, $mini_height, $imagex, $imagey);				
			}
				imagejpeg($temp, rtrim($this->save,'/').'/'.$mini, 100);
				imagedestroy($temp);
				imagedestroy($image); 

			return is_file(rtrim($this->save,'/').'/'.$mini) ? $mini: false;
		}
		public function mark($file = null){
	  		if(!$file || !$this->mark) return false;	  		
	  		list($watermark, $position, $opacity) = explode(',', $this->mark);
	  		if(!is_file($file) || !is_file($watermark)) return $this->error(-5,'FILE='.$file.'||'.'Watermark='.$watermark);
			$type = pathinfo($file, PATHINFO_EXTENSION);
			$types = array('gif','png','jpg','jpeg');
			if(!in_array($type, $types)) return $this->error(-6,$file);
	        $opacity = min($opacity,100);
			$file_data = imagecreatefromstring(file_get_contents($file));
	        $file_width = imagesx($file_data);
	        $file_height = imagesy($file_data);
	        if (in_array(pathinfo($watermark, PATHINFO_EXTENSION), array('gif','png'))) {
	        	$mark_data = imagecreatefromstring(file_get_contents($watermark));
	        	$mark_width = imagesx($mark_data);
	        	$mark_height =  imagesy($mark_data);
		       	switch($position){
		            case 1: $x = 5; $y = 5; break;
		            case 2: $x = ($file_width - $mark_width)/2; $y = $mark_height; break; 
		            case 3: $x = ($file_width - $mark_width)-5; $y = $mark_height; break;
		            case 4: $x = 5; $y = ($file_height - $mark_height) / 2; break;
		            case 5: $x = ($file_width - $mark_width)/2; $y = ($file_height - $mark_height)/2; break;
		            case 6: $x = ($file_width - $mark_width)-5; $y = ($file_height - $mark_height)/2; break;
		            case 7: $x = 5; $y = ($file_height - $mark_height) - 5; break;
		            case 8: $x = ($file_width - $mark_width)/2; $y = ($file_height - $mark_height)-5; break;
		            case 9: $x = ($file_width - $mark_width)-5; $y = ($file_height - $mark_height)-5; break;
		            default: $x = rand(0,($file_width - $mark_width)); $y = rand(0,($file_height - $mark_height));
		        }  
					$temp = imagecreatetruecolor($mark_width, $mark_height);  
				    imagecopy($temp, $file_data, 0, 0, $x, $y, $mark_width, $mark_height);  
				    imagecopy($temp, $mark_data, 0, 0, 0, 0, $mark_width, $mark_height);  
				    imagecopymerge($file_data, $temp, $x, $y, 0, 0, $mark_width, $mark_height, $opacity);			        
			        imagejpeg($file_data, $file, 100);
			        imagedestroy($temp);
			        imagedestroy($file_data);
			        imagedestroy($mark_data);
			        return true;
	        }else{
	        		return $this->error(-6,$watermark);
	        }
		}
		private function error($code = 0, $extends = ''){
			if($code){
			switch ($code) {				
				case 6:  $error = '写入临时文件夹失败'; break;
				case 5:  $error = '写入系统临时文件夹错误'; break;
				case 4:  $error = '没有文件被上传请检查表单'; break;
				case 3:  $error = '文件上传出错上传不完整'; break;
				case 2:  $error = '文件大小超出表单限制'; break;
				case 1:  $error = '文件大小超出系统限制'; break;
				case -1: $error = '上传文件类型不合法'; break;
				case -2: $error = '上传文件大小超出后台限制'; break;		
				case -3: $error = '创建文件保存路径失败'; break;	
				case -4: $error = '保存文件失败请检查路径'; break;	
				case -5: $error = '读取文件错误'; break;	
				case -6: $error = '不支持该操作'; break;	
				default: $error = '未知错误';
			}
				return  '['.$code.']:'.$error.$extends;
			}else{
				return false;	
			}	
		}

}
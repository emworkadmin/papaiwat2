<?php

class IndexController extends Zend_Controller_Action
{
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('login', 'auth', 'default');
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('login', 'auth', 'default');
		}

		$Right_article = $sUser->isAllow("article.article.view");
		$Right_complain = $sUser->isAllow("complain.item.view");
		$Right_poll = $sUser->isAllow("poll.item.view");
		$Right_faq = $sUser->isAllow("faq.question.view");

		$this->view->sUser = $sUser;

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		$model = new Home();
		$modelContentCategory = new Content_Model_Category();
		
		$uservisit = $model->getvisiter();
		$useronline = $model->getuseronline(array("now" => true));
		$useramount = $model->getuseramount();
		$lastlogin = $model->getlastlogin();

		if($Right_article) {
			$newscategory = $modelContentCategory->getOption($defaultLanguage['Code']);
		}
		
		if($Right_complain) {
			$complain = $model->getComplain();
		}

		if($Right_poll) {
			$poll = $model->getpoll($defaultLanguage['Code']);
		}

		if($Right_faq) {
			$question = $model->getquestion($defaultLanguage['Code']);
		}

		$this->view->Right_article = $Right_article;
		$this->view->Right_complain = $Right_complain;
		$this->view->Right_poll = $Right_poll;
		$this->view->Right_faq = $Right_faq;

		$this->view->uservisit = $uservisit;
		$this->view->useronline = $useronline->Amount;
		$this->view->useramount = $useramount;
		$this->view->lastlogin = $lastlogin;
		$this->view->newscategory = isset($newscategory) ? $newscategory : null;
		$this->view->complain = isset($complain) ? $complain : null;
		$this->view->poll = isset($poll) ? $poll : null;
		$this->view->question = isset($question) ? $question : null;
		
		// Set view
		$this->view->pageName = "หน้าแรก";
		
		$_SESSION["cmspathfile"] = "/cms/u" . $sUser->getInfo()->ID;
	}

    function gethotnewsAction()
    {
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }
		$responce = array();
		$responce['result'] = false;
		$responce['data'] = array();

		$CategoryArticleID = $this->getRequest()->getParam("CategoryArticleID");

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$model = new Home();
		$data = $model->getHotNewslist($defaultLanguage['Code'], $CategoryArticleID);

		if(count($data)>0) {
			$i=1;
			foreach($data as $k => $v) {
				$d = array();
				$d['ID'] = $v['ID'];
				$d['NO'] = $i;
				$d['Title'] = $v['Title'];
				$d['Amount'] = $v['Amount'];
				$responce['data'][] = $d;
				$i++;
			}
		}
		$responce['result'] = true;
        $this->_helper->json($responce);
    }
}
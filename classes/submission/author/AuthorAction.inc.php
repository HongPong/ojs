<?php

/**
 * AuthorAction.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package submission
 *
 * AuthorAction class.
 *
 * $Id$
 */

class AuthorAction extends Action{

	/**
	 * Constructor.
	 */
	function AuthorAction() {
		parent::Action();
	}
	
	/**
	 * Actions.
	 */
	 
	/**
	 * Upload the revised version of an article.
	 * @param $articleId int
	 */
	function uploadRevisedVersion($articleId) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($articleId);
		
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($authorSubmission->getRevisedFileId() != null) {
				$fileId = $articleFileManager->uploadSubmissionFile($fileName, $authorSubmission->getRevisedFileId());
			} else {
				$fileId = $articleFileManager->uploadSubmissionFile($fileName);
			}
		}
		
		if (isset($fileId) && $fileId != 0) {
			$authorSubmission->setRevisedFileId($fileId);
			
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
		}
	}
	
	/**
	 * Author completes editor / author review.
	 * @param $articleId int
	 */
	function completeAuthorCopyedit($articleId, $send = false) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$userDao = &DAORegistry::getDAO('UserDAO');
		$journal = &Request::getJournal();
		$user = &Request::getUser();
		
		$email = &new ArticleMailTemplate($articleId, 'COPYEDIT_REVIEW_AUTHOR_COMP');
		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($articleId);
		
		$editAssignment = $authorSubmission->getEditor();
		$editor = &$userDao->getUser($editAssignment->getEditorId());
		
		if ($send) {
			$email->addRecipient($editor->getEmail(), $editor->getFullName());
			$email->setFrom($user->getEmail(), $user->getFullName());
			$email->setSubject(Request::getUserVar('subject'));
			$email->setBody(Request::getUserVar('body'));
			$email->setAssoc(ARTICLE_EMAIL_COPYEDIT_NOTIFY_AUTHOR_COMPLETE, ARTICLE_EMAIL_TYPE_COPYEDIT, $articleId);
			$email->send();
				
			$authorSubmission->setCopyeditorDateAuthorCompleted(Core::getCurrentDate());
			$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
		} else {
			$paramArray = array(
				'editorialContactName' => $editor->getFullName(),
				'articleTitle' => $authorSubmission->getArticleTitle(),
				'journalName' => $journal->getSetting('journalTitle'),
				'authorName' => $user->getFullName()
			);
			$email->assignParams($paramArray);
			$email->displayEditForm(Request::getPageUrl() . '/author/completeAuthorCopyedit/send', array('articleId' => $articleId));
		}
	}
	
	/**
	 * Set that the copyedit is underway.
	 */
	function copyeditUnderway($articleId) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');		
		$authorSubmission = &$authorSubmissionDao->getAuthorSubmission($articleId);
		
		if ($authorSubmission->getCopyeditorDateAuthorNotified() != null && $authorSubmission->getCopyeditorDateAuthorUnderway() == null) {
			$authorSubmission->setCopyeditorDateAuthorUnderway(Core::getCurrentDate());
		}
		
		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
	}	
	
	/**
	 * Upload the revised version of a copyedit file.
	 * @param $articleId int
	 * @param $copyeditStage string
	 */
	function uploadCopyeditVersion($articleId, $copyeditStage) {
		import("file.ArticleFileManager");
		$articleFileManager = new ArticleFileManager($articleId);
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		
		$authorSubmission = $authorSubmissionDao->getAuthorSubmission($articleId);
		
		$fileName = 'upload';
		if ($articleFileManager->uploadedFileExists($fileName)) {
			if ($authorSubmission->getCopyeditFileId() != null) {
				$fileId = $articleFileManager->uploadAuthorFile($fileName, $authorSubmission->getCopyeditFileId());
			} else {
				$fileId = $articleFileManager->uploadAuthorFile($fileName);
			}
		}
	
		$authorSubmission->setCopyeditFileId($fileId);
		
		if ($copyeditStage == 'author') {
			$authorSubmission->setCopyeditorEditorAuthorRevision($articleFileDao->getRevisionNumber($fileId));
		}
		
		$authorSubmissionDao->updateAuthorSubmission($authorSubmission);
	}
	
	//
	// Comments
	//
	
	/**
	 * View editor decision comments.
	 * @param $articleId int
	 */
	function viewEditorDecisionComments($articleId) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = new EditorDecisionCommentForm($articleId, ROLE_ID_AUTHOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post editor decision comment.
	 * @param $articleId int
	 */
	function postEditorDecisionComment($articleId) {
		import("submission.form.comment.EditorDecisionCommentForm");
		
		$commentForm = new EditorDecisionCommentForm($articleId, ROLE_ID_AUTHOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	/**
	 * View copyedit comments.
	 * @param $articleId int
	 */
	function viewCopyeditComments($articleId) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = new CopyeditCommentForm($articleId, ROLE_ID_AUTHOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post copyedit comment.
	 * @param $articleId int
	 */
	function postCopyeditComment($articleId) {
		import("submission.form.comment.CopyeditCommentForm");
		
		$commentForm = new CopyeditCommentForm($articleId, ROLE_ID_AUTHOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}

	/**
	 * View proofread comments.
	 * @param $articleId int
	 */
	function viewProofreadComments($articleId) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_AUTHOR);
		$commentForm->initData();
		$commentForm->display();
	}
	
	/**
	 * Post proofread comment.
	 * @param $articleId int
	 */
	function postProofreadComment($articleId) {
		import("submission.form.comment.ProofreadCommentForm");
		
		$commentForm = new ProofreadCommentForm($articleId, ROLE_ID_AUTHOR);
		$commentForm->readInputData();
		
		if ($commentForm->validate()) {
			$commentForm->execute();
			
		} else {
			parent::setupTemplate(true);
			$commentForm->display();
		}
	}
	
	//
	// Misc
	//
	
	/**
	 * Download a file an author has access to.
	 * @param $articleId int
	 * @param $fileId int
	 * @param $revision int
	 * @return boolean
	 * TODO: Complete list of files author has access to
	 */
	function downloadAuthorFile($articleId, $fileId, $revision = null) {
		$authorSubmissionDao = &DAORegistry::getDAO('AuthorSubmissionDAO');		
		$submission = &$authorSubmissionDao->getAuthorSubmission($articleId);
		$layoutAssignment = &$submission->getLayoutAssignment();

		$canDownload = false;
		
		// Authors have access to:
		// 1) The original submission file.
		// 2) Any files uploaded by the reviewers that are "viewable",
		//    although only after a decision has been made by the editor.
		// 3) The initial copyedit file, after initial copyedit is complete.
		// 4) Any of the author-revised files.
		// 5) The layout version of the file.
		// 6) Any supplementary file
		// 7) Any galley file
		// THIS LIST SHOULD NOW BE COMPLETE.
		if ($submission->getSubmissionFileId() == $fileId) {
			$canDownload = true;
		} else if ($submission->getCopyeditFileId() == $fileId) {
			if ($revision != null) {
				$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');		
				$currentRevision = &$articleFileDao->getRevisionNumber($fileId);
								
				$canDownload = $currentRevision == $revision ? true : false;
			} else {
				$canDownload = true;
			}
		} else if ($submission->getRevisedFileId() == $fileId) {
			$canDownload = true;
		} else if ($layoutAssignment->getLayoutFileId() == $fileId) {
			$canDownload = true;
		} else {
			// Check reviewer files
			foreach ($submission->getReviewAssignments() as $roundReviewAssignments) {
				foreach ($roundReviewAssignments as $reviewAssignment) {
					if ($reviewAssignment->getReviewerFileId() == $fileId) {
						$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
						
						$articleFile = &$articleFileDao->getArticleFile($fileId, $revision);
						
						if ($articleFile != null && $articleFile->getViewable()) {
							$canDownload = true;
						}
					}
				}
			}
			
			// Check supplementary files
			foreach ($submission->getSuppFiles() as $suppFile) {
				if ($suppFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
			
			// Check galley files
			foreach ($submission->getGalleys() as $galleyFile) {
				if ($galleyFile->getFileId() == $fileId) {
					$canDownload = true;
				}
			}
		}
		
		if ($canDownload) {
			return Action::downloadFile($articleId, $fileId, $revision);
		} else {
			return false;
		}
	}
}

?>

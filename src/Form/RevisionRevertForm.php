<?php

/**
 * @file
 * Contains \Drupal\entity\Form\RevisionRevertForm.
 */

namespace Drupal\entity\Form;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity\Revision\EntityRevisionLogInterface;

class RevisionRevertForm extends ConfirmFormBase {

  /**
   * The node revision.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface|\Drupal\entity\Revision\EntityRevisionLogInterface
   */
  protected $revision;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity bundle information.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInformation;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->revision instanceof EntityRevisionLogInterface) {
      return $this->t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionLogMessage())]);
    }
    return $this->t('Are you sure you want to revert the revision?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->revision->toUrl('version-history');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $_revision = NULL) {
    $this->revision = $_revision;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.

    $this->revision = $this->prepareRevertedRevision($this->revision);
    if ($this->revision instanceof EntityRevisionLogInterface) {
      $original_revision_timestamp = $this->revision->getRevisionCreationTime();

      if ($this->revision instanceof EntityRevisionLogInterface) {
        $this->revision->setRevisionLogMessage($this->t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]));
      }
      drupal_set_message(t('@type %title has been reverted to the revision from %revision-date.', ['@type' => $this->getBundleLabel($this->revision), '%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));
    }
    else {
      drupal_set_message(t('@type %title has been reverted', ['@type' => $this->getBundleLabel($this->revision), '%title' => $this->revision->label()]));
    }

    $this->revision->save();

    $this->logger('content')->notice('@type: reverted %title revision %revision.', ['@type' => $this->revision->bundle(), '%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $form_state->setRedirect(
      "entity.{$this->revision->getEntityTypeId()}.version_history",
      array($this->revision->getEntityTypeId() => $this->revision->id())
    );
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The revision to be reverted.
   *
   * @return \Drupal\Core\Entity\RevisionableInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(RevisionableInterface $revision) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);

    return $revision;
  }

  /**
   * Returns a bundle label.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The entity revision.
   *
   * @return string
   */
  protected function getBundleLabel(RevisionableInterface $revision) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $revision */
    $bundle_info = $this->bundleInformation->getBundleInfo($revision->getEntityTypeId());
    if (isset($bundle_info[$revision->bundle()])) {
      return $bundle_info[$revision->bundle()]['label'];
    }
    return $revision->getEntityType()->getLabel();
  }

}

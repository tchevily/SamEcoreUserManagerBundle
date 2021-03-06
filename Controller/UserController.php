<?php

namespace CanalTP\SamEcoreUserManagerBundle\Controller;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use CanalTP\SamCoreBundle\Controller\AbstractController;
use CanalTP\SamEcoreApplicationManagerBundle\Exception\OutOfBoundsException;
use CanalTP\SamEcoreUserManagerBundle\Form\Type\ProfilFormType;
use CanalTP\SamEcoreUserManagerBundle\Entity\User;
use CanalTP\SamEcoreUserManagerBundle\Form\Flow\RegistrationFlow;

class UserController extends AbstractController
{
    private $userManager = null;

    /**
     * Lists all User entities.
     */
    public function listAction()
    {
        $this->isAllowed('BUSINESS_VIEW_USER');

        $userManager = $this->container->get('fos_user.user_manager');
        $user = $this->get('security.context')->getToken()->getUser();
        $isSuperAdmin = $user->hasRole('ROLE_SUPER_ADMIN');
        if ($isSuperAdmin) {
            $entities = $this->container->get('sam.user_manager')->findUsers();
        } else {
            $entities = $userManager->findUsersBy(array('customer' => $user->getCustomer()));
        }

        $deleteFormViews = array();
        foreach ($entities as $entity) {
            $id                   = $entity->getId();
            $deleteForm           = $this->createDeleteForm($id);
            $deleteFormViews[$id] = $deleteForm->createView();
        }

        return $this->render(
            'CanalTPSamEcoreUserManagerBundle:User:list.html.twig',
            array(
                'entities'     => $entities,
                'isSuperAdmin' => $isSuperAdmin,
                'delete_forms' => $deleteFormViews,
            )
        );
    }

    public function editAction(Request $request, User $user = null)
    {
        $this->isGranted('BUSINESS_MANAGE_USER');

        $isNew = ($user == null);
        $flow = $this->get('sam.registration.form.flow');
        $flow->bind(($isNew ? new User() : $user));
        $form = $flow->createForm();

        if ($flow->isValid($form)) {
            $user = $form->getData();

            $flow->saveCurrentStepData($form);
            $user->setStatus($flow->getCurrentStep());
            $this->get('fos_user.registration.form.handler')->save(
                $user,
                false
            );
            $isNew = false;
            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                $flow->reset();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'profile.flash.updated'
                );

                return $this->redirect($this->generateUrl('sam_user_list'));
            }
        }

        return $this->render('CanalTPSamEcoreUserManagerBundle:User:edit.html.twig', array(
            'id' => ($isNew ? !$isNew : $user->getId()),
            'title' => ($isNew ? 'ctp_user.user.add._title' : 'ctp_user.user.edit._title'),
            'form' => $form->createView(),
            'flow' => $flow
        ));
    }

    /**
     * Deletes a User entity.
     */
    public function deleteAction(Request $request, $id)
    {
        $this->isAllowed('BUSINESS_MANAGE_USER');

        $form = $this->createDeleteForm($id);

        if ($request->getMethod() == 'GET') {
            $userManager = $this->container->get('fos_user.user_manager');
            $entity = $userManager->findUserBy(array('id' => $id));

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            return $this->render(
                'CanalTPSamEcoreUserManagerBundle:User:delete.html.twig',
                array(
                    'entity'      => $entity,
                    'delete_form' => $form->createView(),
                )
            );
        } else {
            $form->bind($request);

            if ($form->isValid()) {
                if ($this->getUser()->getId() == $id) {
                    throw new \Symfony\Component\Security\Core\Exception\AccessDeniedException('Seriously, you shouldn\'t delete your account.');
                }

                //Use sam user manager ;)
                $userManager = $this->container->get('sam.user_manager');
                $entity = $userManager->findUserBy(array('id' => $id));

                if (!$entity) {
                    throw $this->createNotFoundException('Unable to find User entity.');
                }

                $userManager->deleteUser($entity);
            }

            return $this->redirect($this->generateUrl('sam_user_list'));
        }
    }

    /**
     * Creates a form to delete a User entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }

    public function editProfilProcessForm($user)
    {
        $this->get('sam.user_manager')->updateUser($user);
        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('ctp_user.profil.edit.validate')
        );
    }

    /**
     * Displays a form to edit profil of current user.
     */
    public function editProfilAction()
    {
        $app = $this->get('canal_tp_sam.application.finder')->getCurrentApp();
        $id = $this->get('security.context')->getToken()->getUser()->getId();
        $userManager = $this->container->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array('id' => $id));
        $form = $this->createForm(
            new ProfilFormType(),
            $user
        );

        $form->handleRequest($this->getRequest());
        if ($form->isValid()) {
            $this->editProfilProcessForm($user);
        }
        return $this->render(
            'CanalTPSamEcoreUserManagerBundle:User:profil.html.twig',
            array(
                'form' => $form->createView(),
                'defaultAppHomeUrl' => $app->getDefaultRoute()
            )
        );
    }

    public function toolbarAction()
    {
        $appCanonicalName = $this->get('canal_tp_sam.application.finder')->getCurrentApp()->getCanonicalName();

        return $this->render(
            'CanalTPSamEcoreUserManagerBundle:User:toolbar.html.twig',
            array('currentAppName' => $appCanonicalName)
        );
    }
}

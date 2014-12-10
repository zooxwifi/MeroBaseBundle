<?php

namespace Mero\BaseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Mero\BaseBundle\Entity\AbstractEntity;

/**
 * Classe abstrata para criação de CRUD simples
 *
 * @package Mero\BaseBundle\Controller
 * @author Rafael Mello <merorafael@gmail.com>
 * @link https://github.com/merorafael/MeroBaseBundle Repositório do projeto
 * @copyright Copyright (c) 2014 - Rafael Mello
 * @license https://github.com/merorafael/MeroBaseBundle/blob/master/LICENSE BSD license
 */
abstract class AbstractCrudController extends Controller
{
    
    /**
     * @var string Nome da rota para indexAction
     */
    const indexRoute = 'index';
    
    /**
     * @var string Nome da rota para addAction
     */
    const addRoute = 'add';
    
    /**
     * @var string Nome da rota para editAction
     */
    const editRoute = 'edit';
    
    /**
     * @var string Nome da rota para redirecionamento pós-inserção.
     */
    const createdRoute = null;
    
    /**
     * @var string Nome da rota para redirecionamento pós-atualização.
     */
    const updatedRoute = null;
    
    /**
     * @var string Nome da rota para redirecionamento pós-exclusão.
     */
    const removedRoute = null;
    
    /**
     * Retorna namespace relacionada a entidade.
     * Sobreescreva este método caso o namespace seja diferente do padrão.
     * 
     * Namespace padrão: \<Namespace do bundle>\Entity
     *
     * @return string Namespace da entidade
     */
    protected function getEntityNamespace()
    {
        return '\\'.str_replace('\Controller', '\Entity', __NAMESPACE__);
    }
    
    /**
     * Classe referente a entidade.
     * 
     * @return string
     */
    abstract protected function getEntityName();
    
    /**
     * Retorna nome referente ao bundle.
     * 
     * @return string Nome do bundle
     */
    protected function getBundleName()
    {
        return $this->getRequest()->attributes->get('_template')->get('bundle');
    }
    
    /**
     * Retorna objeto relacionado ao Type do formulário.
     * 
     * @return \Symfony\Component\Form\AbstractType Objeto do tipo do formulário
     */
    abstract protected function getFormType();
    
    /**
     * Retorna prefixo a ser usado para a rota.
     * 
     * @return string
     */
    public function getRoute($action = null)
    {
        //return strtolower(str_replace('Controller', '', get_class($this))).strtolower(str_replace('Action', '', $action));
    }
    
    /**
     * Retorna gerenciador de entidades(Entity Manager) do Doctrine.
     * 
     * @return \Doctrine\ORM\EntityManager Entity Manager do Doctrine
     */
    public function getDoctrineManager()
    {
        return $this->getDoctrine()->getManager();
    }
    
    /**
     * Retorna campo padrão utilizado para ordenação de dados.
     * 
     * @return string Campo da entity
     */
    protected function defaultSort()
    {
        return 'created';
    }
    
    /**
     * Método utilizado em classes extendidas para alterar Query Builder padrão
     * utilizado pelo método indexAction.
     * 
     * @see http://doctrine-orm.readthedocs.org/en/latest/reference/query-builder.html Documentação do Query Builder pelo Doctrine
     * @see \Mero\BaseBundle\Controller::indexAction() Action referente a index do CRUD
     * 
     * @param \Doctrine\ORM\QueryBuilder $entity_q Entrada do Query Builder em indexAction
     * @return \Doctrine\ORM\QueryBuilder Query Builder processado pelo método
     */
    protected function indexQueryBuilder(QueryBuilder $entity_q)
    {
        return $entity_q;
    }

    /**
     * Método utilizado em classes extendidas para manipular dados da entidade que não 
     * correspondem a um CRUD simples.
     * 
     * @param \Mero\BaseBundle\Entity\AbstractEntity $entity Entity referente ao CRUD
     */
    protected function dataManager(AbstractEntity $entity) 
    {
        return $entity;
    }
    
    /**
     * Cria o formulário de inserção de dados baseado na entidade informada.
     * 
     * @param \Mero\BaseBundle\Entity\AbstractEntity $entity Entity referente ao CRUD
     * @return \Symfony\Component\Form\Form Formulário do Symfony
     */
    protected function getInsertForm(AbstractEntity $entity)
    {
        $form = $this->createForm($this->getType(), $entity, array(
            'action' => $this->generateUrl(),
            'method' => 'POST'
        ));
        $form->add('submit', 'submit');
        return $form;
    }
    
    /**
     * Cria o formulário de alteração de dados baseado na entidade informada.
     * 
     * @param \Mero\BaseBundle\Entity\AbstractEntity $entity Entity referente ao CRUD
     * @return \Symfony\Component\Form\Form Formulário do Symfony
     */
    protected function getUpdateForm(AbstractEntity $entity)
    {
        $form = $this->createForm($this->getType(), $entity, array(
            'action' => $this->generateUrl(static::editRoute, array(
                'id' => $entity->getId()
            )),
            'method' => 'PUT'
        ));
        $form->add('submit', 'submit');
        return $form;
    }
    
    /**
     * Método responsável por adicionar novos registros
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    private function addData(Request $request)
    {
        $entity_class = $this->getEntityNamespace()."\\".$this->getEntityName();
        if (!class_exists($entity_class)) {
            throw $this->createNotFoundException('Entity not found');
        }
        $entity = new $entity_class();
        $form = $this->getInsertForm($entity);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $entity = $this->dataManager($entity);
                $em = $this->getDoctrine()->getManager();
                $em->persist($entity);
                $em->flush();
                $this->get('session')
                    ->getFlashBag()
                    ->add('success', 'Operação realizada com sucesso.');
                return $this->redirect($this->generateUrl(is_null(static::createdRoute) ? static::indexRoute : static::createdRoute));
            } else {
                $this->get('session')
                    ->getFlashBag()
                    ->add('danger', 'Falha ao realizar operação.');
            }
        }
        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }
    
    /**
     * Método responsável por alterar registros
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id Identificação do registro
     * @return array
     */
    private function editData(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getBundleName().":".$this->getEntityName())->find($id);
        if (!$entity) {
            $this->get('session')
            ->getFlashBag()
            ->add('danger', 'Registro não encontrado.');
            return $this->redirect($this->generateUrl(is_null(static::updatedRoute) ? static::indexRoute : static::updatedRoute));
        }
        $form = $this->getUpdateForm($entity);
        if ($request->isMethod('PUT')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $entity = $this->dataManager($entity);
                $em->persist($entity);
                $em->flush();
                $this->get('session')
                    ->getFlashBag()
                    ->add('success', 'Operação realizada com sucesso.');
                return $this->redirect($this->generateUrl(is_null(static::updatedRoute) ? static::indexRoute : static::updatedRoute));
            } else {
                $this->get('session')
                    ->getFlashBag()
                    ->add('danger', 'Falha ao realizar operação.');
            }
        }
        return array(
            'entity' => $entity,
            'form' => $form->createView()
        );
    }
    
    /**
     * Action de listagem dos registros
     * 
     * Os dados exibidos são controlados com parâmetros $_GET
     * page - Qual página está sendo exibida(padrão 0);
     * limit - Quantidade de registros por página(padrão 10);
     * sort - Campo a ser utilizado para ordenação(padrão "created")
     * order - Como será ordernado o campo sort(padrão DESC)
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id Utilizado para editar um registro na indexAction caso informado
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @Route("/{id}", defaults={"id": null}, requirements={"id": "\d+"})
     */
    public function indexAction(Request $request, $id)
    {
        $page = $request->query->get('page') ? $request->query->get('page') : 1;
        $limit = $request->query->get('limit') ? $request->query->get('limit') : 10;
        
        $em = $this->getDoctrine()->getManager();
        $entity_q = $em->createQueryBuilder()
            ->select('e')
            ->from($this->getBundleName().":".$this->getEntityName(), 'e')
        ;
        if (!$request->query->get('sort')) {
            $entity_q->orderBy("e.{$this->defaultSort()}", "DESC");
        }
        
        $entity_q = $this->indexQueryBuilder($entity_q);
        
        //Recurso dependente do KnpPaginatorBundle
        $entities = $this->get('knp_paginator')->paginate($entity_q->getQuery(), $page, $limit);
        
        //Adiciona formulário de CRUD(adicionar ou editar de acordo com a identificação informada).
        $crud = !empty($id) ? $this->editData($request, $id) : $this->addData($request);
        if (!is_array($crud)) {
            return $crud;
        }
        
        return $this->render($this->getBundleName().":".$this->getEntityName().":index.html.twig", array_merge(
            $crud,
            array(
                'entities' => $entities
            )
        ));
    }
    
    /**
     * Action para exibir detalhes de registro especifico
     * 
     * @param integer $id Identificação do registro
     * 
     * @Route("/detalhes/{id}", requirements={"id": "\d+"})
     */
    public function detailsAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getBundleName().":".$this->getEntityName())->find($id);
        if (!$entity) {
            $this->get('session')
                ->getFlashBag()
                ->add('danger', 'Registro não encontrado.');
            return $this->redirect($this->generateUrl(static::indexRoute));
        }
        return $this->render($this->getBundleName().":".$this->getEntityName().":details.html.twig", array(
            'entity' => $entity
        ));
    }
    
    /**
     * Action para adicionar novos registros
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * 
     * @Route("/add")
     */
    public function addAction(Request $request)
    {
        $crud = $this->addData($request);
        if (!is_array($crud)) {
            return $crud;
        }
        return $this->render($this->getBundleName().":".$this->getEntityName().":add.html.twig", $crud);
    }
    
    /**
     * Método action responsável por alteração de registros
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id Identificação do registro
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * 
     * @Route("/edit/{id}", requirements={"id": "\d+"})
     */
    public function editAction(Request $request, $id)
    {
        $crud = $this->editData($request, $id);
        if (!is_array($crud)) {
            return $crud;
        }
        return $this->render($this->getBundleName().":".$this->getEntityName().":edit.html.twig", $crud);
    }
    
    /**
     * Método action responsável por remoção de registros
     * 
     * @param integer $id Identificação do registro
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * 
     * @Route("/remove/{id}", requirements={"id": "\d+"})
     */
    public function removeAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository($this->getBundleName().":".$this->getEntityName())->find($id);
        if (!$entity) {
            $this->get('session')
                ->getFlashBag()
                ->add('danger', 'Registro não encontrado.');
        } else {
            $em->remove($entity);
            $em->flush();
            $this->get('session')
                ->getFlashBag()
                ->add('success', 'Operação realizada com sucesso.');
        }
        return $this->redirect($this->generateUrl(is_null(static::removedRoute) ? static::indexRoute : static::removedRoute));
    }
}
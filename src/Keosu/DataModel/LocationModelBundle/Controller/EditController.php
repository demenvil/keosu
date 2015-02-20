<?php
/************************************************************************
 Keosu is an open source CMS for mobile app
Copyright (C) 2014  Vincent Le Borgne, Pockeit

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
************************************************************************/

namespace Keosu\DataModel\LocationModelBundle\Controller;

use Keosu\DataModel\LocationModelBundle\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Keosu\DataModel\LocationModelBundle\Form\LocationTagsType;

/**
 * Controller to edit an Location element
 * @author vleborgne
 *
 */
class EditController extends Controller {

	/**
	 * add Location object action
	 */
	public function addAction() {
		$poi = new Location();
		$poi->setLat(48.117266);
		$poi->setLng(-1.6777925999999752);
		return $this->editLocation($poi);
	}

	/**
	 * Edit Location object action
	 */
	public function editAction($id) {
		$em = $this->getDoctrine()->getManager();
		$poi = $em->getRepository('KeosuDataModelLocationModelBundle:Location')->find($id);
		return $this->editLocation($poi);
	}
	/**
	 * delete Location object action
	 */
	public function deleteAction($id) {
		$em = $this->getDoctrine()->getManager();
		$poi = $em->getRepository('KeosuDataModelLocationModelBundle:Location')->find($id);

		if ($poi->getReader() === null) {
			$em->remove($poi);
			$em->flush();
		}
		return $this->redirect($this->generateUrl('keosu_location_viewlist'));
	}

	/**
	 * Common function to edit/add a poi
	 * Manage form and store object in database
	 */
	private function editLocation($poi) {
		
		//Get tags list from database
		$em = $this->get('doctrine')->getManager();
		$query = $em->createQuery('SELECT DISTINCT u.tagName FROM Keosu\DataModel\LocationModelBundle\Entity\LocationTags u');
		$tagsResult = $query->getResult();
		$tagsList=array();
		foreach ($tagsResult as $tag){
			$tagsList[]=$tag['tagName'];
		}
	
		$form = $this->getLocationForm($poi);

		$originalTags = array();
		if ($poi->getTags() != [])
			foreach ($poi->getTags() as $tag)
				$originalTags[] = $tag;

		$request = $this->get('request');

		//If we are in POST method, form is submit
		if ($request->getMethod() == 'POST') {
			$form->bind($request);

			if ($form->isValid()) {
				//Identify tags to delete
				foreach ($poi->getTags() as $tag) {
					foreach ($originalTags as $key => $toDel) {
						if ($toDel->getId() === $tag->getId()) {
							unset($originalTags[$key]);
						}
					}
				}
				//Deleting tag from article and database
				foreach ($originalTags as $tag) {
					$tag->getLocation()->removeTag($tag);
					$em->remove($tag);
				}


				$em = $this->get('doctrine')->getManager();
				$em->persist($poi);
				$em->flush();
				return $this->redirect(
							$this->generateUrl('keosu_location_viewlist'));
			}
		}
		return $this
				->render('KeosuDataModelLocationModelBundle:Edit:edit.html.twig',
						array('form' => $form->createView(),
								'poi' => $poi,
								'tagsList'=> $tagsList));
	}

	/**
	 * Build POI specific form
	 */
	private function getLocationForm($poi) {
		return $this->createFormBuilder($poi)
				->add('name', 'text')
				->add('description', 'text')
				->add('lat', 'text')
				->add('lng', 'text')
				->add('enableComments','checkbox',array(
						'required' => false,
				))
				->add('tags', 'collection', array(
						'type'         => new LocationTagsType(),
						'allow_add'    => true,
						'allow_delete' => true,
						'by_reference' => false,
						'required'     => false,
						'label'        => false
				))
				->getForm();
	}
}

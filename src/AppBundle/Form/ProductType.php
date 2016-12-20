<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku')
            ->add('ref')
            ->add('name')
            ->add('available')
            ->add('selected')
            ->add('price')
            ->add('priceUnit', null, [
                'empty_data' => '1'
            ])
            ->add('shortDescription')
            ->add('portionWeight', null, [
                'empty_data' => '500'
            ])
            ->add('portionNumber', null, [
                'empty_data' => '1'
            ])
            ->add('tax', null, [
                'empty_data' => '1'
            ])
            ->add('origin')
            ->add('bio')
            ->add('user')
            ->add('photoFile');
        ;
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
            ]
        );
    }
}

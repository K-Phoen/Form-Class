<?php
/**
 * Form Class, ou comment créer et utiliser facilement un formulaire
 * 
 * \todo optimiser !
 *
 * Vous êtes libre d'utiliser et de distribuer ce script comme vous 
 * l'entendez, en gardant à l'esprit que ce script est, à l'origine, fait 
 * par des développeurs bénévoles : en conséquence, veillez à laisser le 
 * Copyright, par respect de ceux qui ont consacré du temps à la création 
 * du script.
 *
 * @package        Form Class
 * @author         Kévin Gomez <geek63@gmail.com>
 * @copyright      © Kévin Gomez, La Geek Attitude 2007, 2010
 * @link           http://blog.kevingomez.fr/ La Geek Attitude
 * @license        http://www.gnu.org/licenses/gpl.html (COPYING) GNU Public License
 * @begin          10/01/2009, Kévin Gomez
 * @last           18/05/2010, Kévin Gomez
*/


/**
*
*   Class de création de formulaires
*
**/
class Form {

    protected $attrs;
    protected $label_suffix = ' : ';
    protected $values = array();
    protected $fields = array();
    protected $errors_msg = array();

    public function __construct($method='post', $action='')
    {
        $this->attrs = new AttributeList(array(
                                            'method'    => $method,
                                            'action'    => $action,
                                            )
                                        );
    }

    /**
     * Ajouter un champ
     *
     * @param $type type de champ à ajouter (correspond au nom de l'objet réprésentant le champ)
     * @param $name nom du champ (doit être unique)
     * @param $value valeur par défaut
     *
     * @return obj (retourne l'objet représentant le champ créé)
     */
    public function add($type, $name='', $value='', $label='')
    {
        if(is_object($type))
        {
            if(isset($this->fields[$type->getName()]))
                trigger_error('Un champ nommé « '.$type->getName().' » existe déjà.', E_USER_ERROR);
            
            $this->fields[$type->getName()] = $type;
        }
        else
        {
            if(empty($name))
                trigger_error('L\'argument name est nécessaire pour la création du champ.', E_USER_ERROR);
            
            if(isset($this->fields[$name]))
                trigger_error('Un champ nommé « '.$name.' » existe déjà.', E_USER_ERROR);
            else
            {
                $field = 'Field_'.$type;

                if($field == 'Field_SubmitButton')
                    $o_Field = new $field($name);
                else
                    $o_Field = new $field($name, $value, $label);
                
                $o_Field->setForm($this);
                
                $this->fields[$name] = $o_Field;

                return $o_Field;
            }
        }
    }

    /**
     * Remplis les champs avec l'array fourni
     * 
     * \todo Refaire : parcourir $data plutôt que $this->fields
     *
     * @param $data ('clef' => 'valeur') où clef == nom du champ
     *
     * @return void
     */
    public function bound(array &$data)
    {
        foreach($this->fields as $name => $field)
        {
            if($field->getType() == 'submit')
                continue;
            
            $field->setValue((isset($data[$name])) ? $data[$name] : Null);
        }
    }

    /**
     * Indique si les champs du formulaire ont été correctement remplis
     * (méthode à appeler si on veut générer les erreurs)
     *
     * @return bool
     */
    public function isValid()
    {
        $valid = True;
        foreach($this->fields as $name => $field)
        {
            if($field->isDisabled())
                continue;
            
            if(!$field->isValid())
            {
                $valid = False;
                foreach($field->getErrors() as $error)
                    $this->triggerError($field, $error);
            }

            $this->values[$field->getName()] = $field->getValue();
        }

        return ($valid AND count($this->errors_msg) == 0);
    }

    /**
     * Ajoute un message d'erreur à la liste des erreurs
     *
     * @param $field nom du champ ou objet représentant le champ lié à l'erreur
     * @param $error_text message d'erreur à afficher
     *
     * @return void
     */
    public function triggerError($field, $error_text)
    {
        if(!is_string($field))
            $oField = $field;
        else
        {
            if(!isset($this->fields[$field]))
                trigger_error('Aucun champ nommé « '.$field.' » n\'a été déclaré.', E_USER_ERROR);
            
            $oField = $this->fields[$field];
        }
        
        if(isset($oField))
            $this->errors_msg[] = array(
                                        'ERROR_TEXT' => $error_text,
                                        'FIELD_LABEL' => $oField->getLabel()
                                    );
    }

    /**
     * Récupère l'objet représentant le champ ayant pour code $field_name
     *
     * @param $field_name nom du champ
     *
     * @return obj
     */
    public function field($field_name)
    {
        if(isset($this->fields[$field_name]))
            return $this->fields[$field_name];
        else
            trigger_error('Aucun champ nommé « '.$field_name.' » n\'a été créé.', E_USER_ERROR);
    }

    /**
     * Retourne une valeur du formulaire (ou toute si pas de clef fournie)
     * si la clef ne correspond pas à un champ et qu'une valeur par défaut 
     * est donnée, on la renvoie
     *
     * \warning Ne fonctionne qu'après un appel à la méthode isValid() !
     *
     * @param $var nom d'un item du formulaire dont on veut la valeur
     * @param $default valeur à renvoyer si pas de correspondance avec un champ
     *
     * @return mixed
     */
    public function get($val='', $default='|NoDefaultValue|')
    {
        if(empty($val))
            return $this->values;
        elseif(isset($this->values[$val]))
            return $this->values[$val];
        elseif($default != '|NoDefaultValue|')
            return $default;
        else
            trigger_error('Aucun champ nommé « '.$val.' » n\'a été créé, il est donc impossible de récupérer sa valeur.', E_USER_ERROR);
    }

    /**
     * Retourne un array contenant les messages d'erreur
     * array de la forme ::
     * array(
     *         'ERROR_TEXT' => "message d'erreur",
     *         'FIELD_LABEL' => "label du champ"
     *     )
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors_msg;
    }

    /**
     * Change l'enctype du formulaire
     *
     * @param $enctype multipart/form-data ou application/x-www-form-urlencoded
     *
     * @return obj (le formulaire en question)
     */
    public function setEnctype($enctype)
    {
        $enctype = strtolower($enctype);
        if (in_array($enctype, array('multipart/form-data', 'application/x-www-form-urlencoded')))
            $this->attrs['enctype'] = $enctype;

        return $this;
    }
    
    /**
     * Change l'attribut "name" du formulaire
     * 
     * @param $name La nouvelle valeur de l'attribut
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->attrs['name'] = $name;
        
        return $this;
    }
    
    /**
     * Change l'ID du formulaire
     *
     * @param $text nouvelle valeur
     *
     * @return obj (le champ en question)
     */
    public function setID($id)
    {
        $this->attrs['id'] = $id;
        
        return $this;
    }
    
    /**
     * Retourne l'ID du champ
     *
     * @return string
     */
    public function getID()
    {
        return $this->attrs['id'];
    }
    
    /**
     * Texte à afficher après le label du champ
     *
     * @param $string nouvelle valeur
     *
     * @return obj (le champ en question)
     */
    public function setLabelSuffix($string)
    {
        $this->label_suffix = $string;
        
        return $this;
    }
    
    /**
     * Retourne le texte à afficher après le label du champ
     *
     * @return string
     */
    public function getLabelSuffix()
    {
        return $this->label_suffix;
    }
    
    /**
     * Retourne le form préparé pour que le moteur de TPL puisse l'exploiter
     *
     * @return array('nom_du_champ' => array('code_html', 'label', array('mess erreur 1', 'mess erreur 2')))
     */
    public function toArray()
    {
        $output = array();
        foreach($this->fields as $name => $field)
        {
            $output[$name] = array(
                                    'HTML'   => (string) $field, //on caste en string pour récupérer le code html
                                    'LABEL'  => $field->getLabel(),
                                    'ERRORS' => $field->getErrors(),
                                );
        }

        return $output;
    }

    /**
     * Retourne le form au format HTML, prêt à être utilisé
     * les champs sont encadrés par des <p>
     * 
     * @param $display_errors dés/active l'affichage auto des erreurs
     *
     * @return string
     */
    public function asP($display_errors=False)
    {
        $output = sprintf('<form %s>', $this->attrs);
        
        $errors = '<h3>Erreur</h3><ul class="form_error">';

        foreach($this->fields as $field)
        {
            if($field->getType() != 'hidden')
            {
                if($field->getErrors())
                    $errors .= '<li>'.$field->getLabel().$this->label_suffix.' '.implode(', ', $field->getErrors()).'</li>';
                
                $output .= "\n\t".'<p>';

                if($field->getLabel() != '')
                    $output .= "\n\t\t".'<label for="'.$field->getID().'">'.$field->getLabel().$this->label_suffix.'</label>';

                $output .= "\n\t\t".$field->toHTML();
                $output .= "\n\t".'</p>';
            }
            else
            {
                $output .= "\n\t".$field->toHTML();
            }
        }
        
        $errors .= '</ul>';

        $output .= "\n\r".'</form>';

        return ($display_errors) ? $errors.$output : $output;
    }

    public function __toString()
    {
        return $this->asP();
    }
}


/**
*
*   Class de base aux divers champs
*
**/
abstract class FormField {

    protected $class = array();
    protected $attrs;
    protected $form_ref = Null;
    protected $label = '';
    protected $required = True;
    protected $auto_bound = True;
    protected $error_messages = array();
    protected $user_validation_rules = array();
    protected $errors_list = array(
                                'required'  => 'Ce champ est obligatoire.',
                            );
    

    public function __construct($name, $label='')
    {
        $this->attrs = new AttributeList(array('name' => $name));
        $this->attrs['id'] = $this->getName();
        $this->attrs['value'] = '';
        $this->setLabel($label);
    }
    
    /**
     * Donne au champ une référence vers le formulaire
     * 
     * @param &$form Référence à l'instance du formulaire qui a créé le champ
     * 
     * @return void
     */
    public function setForm(Form &$form)
    {
        if($this->form_ref != Null)
            return;
        
        $this->form_ref = $form;
    }

    /**
     * Dit si le contenu du champ est valide et crée les erreurs si besoin
     * (pourra et devra être surchargée)
     *
     * @return bool
     */
    public function isValid()
    {
        // empty considérant les valeurs telles que 0 comme vides 
        if($this->required AND (isset($this->attrs['value']) AND $this->attrs['value'] == ''))
        {
            $this->_error('required');
            return False;
        }

        return True;
    }
    
    /**
     * Ajoute une règle de validation
     *
     * @param $callback fonction/méthode de validation.
     *                  doit accepter un paramètre, et retourner le 
     *                  texte de l'erreur (si erreur), ou une chaine 
     *                  vide si tout est OK
     * @param $args Paramètres à passer au callback (en plus de ceux par défaut)
     *
     * @return obj (le champ en question)
     */
    public function addValidationRule($callback, array $args=array())
    {
        $this->user_validation_rules[] = array($callback, $args);
        
        return $this;
    }
    
    /**
     * Indique si le formulaire passe les règles de validation personnalisées
     *
     * @return bool
     */
    protected function passUserValidationRules()
    {
        foreach($this->user_validation_rules as $key => $rule)
        {
            $args = array($this->getValue(), $this->form_ref);
            
            $pass = call_user_func_array($rule[0], array_merge($args, $rule[1]));
            
            if(!empty($pass))
            {
                $this->_userError($pass);
                return False;
            }
        }
        
        return True;
    }

    /**
     * Rend obligatoire le champ ou pas
     *
     * @param $bool status du champ
     *
     * @return obj (le champ en question)
     */
    public function required($bool=True)
    {
        $this->required = (bool) $bool;

        return $this;
    }
    
    /**
     * Dés/active un champ
     *
     * @param $bool état d'activation
     *
     * @return obj (le champ en question)
     */
    public function disabled($bool=True)
    {
        if((bool) $bool)
            $this->attrs['disabled'] = 'disabled';
        else
            unset($this->attrs['disabled']);

        return $this;
    }
    
    /**
     * Indique si le champ est désactivé
     * 
     * @return bool
     */
    public function isDisabled() {
        return isset($this->attrs['disabled']) && $this->attrs['disabled'] == 'disabled';
    }
    
    /**
     * Dés/active la lecture seule
     *
     * @param $bool état d'activation
     *
     * @return obj (le champ en question)
     */
    public function readOnly($bool=True)
    {
        if((bool) $bool)
            $this->attrs['readonly'] = 'readonly';
        else
            unset($this->attrs['readonly']);

        return $this;
    }

    /**
     * Dés/Active la remplissage auto lors de l'appel à la méthode bound()
     * Empêche simplement la valeur de s'afficher, mais elle reste présente et récupérable via getValue() par ex
     *
     * @param $bool état (True == activé)
     *
     * @return obj (le champ en question)
     */
    public function autoBound($bool=True)
    {
        $this->auto_bound = (bool) $bool;

        return $this;
    }

    /**
     * Donne l'état du remplissage auto
     *
     * @return bool
     */
    public function canBound()
    {
        return $this->auto_bound;
    }

    /**
     * Retourne le nom du champ
     * 
     * \note Si le champ est un tableau, son attribut name se termine par
     *       [] dans le code HTML. Cette méthode supprime les crochets.
     *
     * @return string
     */
    public function getName()
    {   
        return substr($this->attrs['name'], -2) == '[]' ? substr($this->attrs['name'], 0, -2) : $this->attrs['name'];
    }

    /**
     * Retourne l'ID du champ
     *
     * @return string
     */
    public function getID()
    {
        return $this->attrs['id'];
    }

    /**
     * Retourne le type du champ
     *
     * @return string
     */
    public function getType()
    {
        return (!empty($this->attrs['type'])) ? $this->attrs['type'] : '';
    }

    /**
     * Retourne le contenu du label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Retourne les messages d'erreurs
     *
     * @return array
     */
    public function getErrors()
    {
        return array_values($this->error_messages);
    }

    /**
     * Retourne la valeur du champ
     *
     * @return string
     */
    public function getValue()
    {
        return isset($this->attrs['value']) ? $this->attrs['value'] : '';
    }

    /**
     * Retourne la valeur du champ (nettoyée) (à surcharger dans une class-fille)
     *
     * @return string
     */
/*
    public function getCleanedValue($value='')
    {
        return (empty($value)) ? $this->attrs['value'] : $value;
    }
*/

    /**
     * Ajoute une _class CSS au champ
     *
     * @param $class nom de la class
     *
     * @return obj (le champ en question)
     */
    public function addClass($class)
    {
        if(is_array($class))
        {
            foreach($class as $item)
                $this->addClass($item);
        }
        else
        {
            if(!in_array($class, $this->class))
                $this->class[] = $class;
        }

        return $this;
    }

    /**
     * Génère le contenu de l'attribut _class_ avec tous les éléments données
     * 
     * \note Méthode à appeler en début de __toString()
     *
     * return void
     */
    protected function _makeClass()
    {
        if(!empty($this->class))
            $this->attrs['class'] = implode(' ', $this->class);
    }

    /**
     * Change la valeur d'un champ du formulaire
     *
     * @param $text nouvelle valeur
     *
     * @return obj (le champ en question)
     */
    public function setValue($text)
    {
        $this->attrs['value'] = $text;

        return $this;
    }

    /**
     * Change la valeur du label
     *
     * @param $text nouvelle valeur
     *
     * @return obj (le champ en question)
     */
    public function setLabel($text)
    {
        $this->label = $text;

        return $this;
    }

    /**
     * Change la valeur de l'ID du champ
     *
     * @param $text nouvelle valeur
     *
     * @return obj (le champ en question)
     */
    public function setID($text)
    {
        $this->attrs['id'] = $text;

        return $this;
    }
    
    /**
     * Permet de changer le message affiché lors d'une erreur
     *
     * @param $error_id id du message d'erreur
     * @param $text nouveau message
     *
     * @return obj (le champ en question)
     */
    public function setErrorText($error_id, $text)
    {
        $this->errors_list[$error_id] = $text;
        
        return $this;
    }

    /**
     * Ajoute le message d'erreur correspondant à $id dans la liste d'erreurs
     *
     * @param $id identifiant de l'erreur
     *
     * @return void
     */
    protected function _error()
    {
        $args = func_get_args();
        $id = array_shift($args);
        
        $this->error_messages[$id] = vsprintf($this->errors_list[$id], $args);
    }
    
    
    /**
     * Ajoute le message d'erreur dans la liste d'erreurs
     * méthode utilisée pour les erreurs provoquées par des règles de validation
     * provenants de l'utilisateur
     *
     * @param $error erreur
     *
     * @return void
     */
    protected function _userError($error)
    {
        $this->error_messages[time()] = $error;
    } 
    
    public abstract function toHTML();
    
    public function __toString() {
        return $this->toHTML();
    }
}


abstract class FormInput extends FormField {

    protected $min_lenght = 0;

    public function __construct($name, $label='')
    {
        parent::__construct($name, $label);
        $this->attrs['type'] = 'text';

        $this->setErrorText('minlength', 'Le texte est trop court (au moins %d caractères).');
        $this->setErrorText('maxlength', 'Le texte est trop long (pas plus de %d caractères).');
    }
    
    /**
     * Permet de définir la taille du champ
     * 
     * @param $size La nouvelle taille (0 pour supprimer cet attribut)
     * 
     * @return $this
     */
    public function setSize($size)
    {
        if(is_numeric($size))
        {
            if((int) $size > 0)
                $this->attrs['size'] = (int) $size;
            elseif((int) $size == 0)
                unset($this->attrs['size']);
        }

        return $this;
    }

    /**
     * Change la longueur minimale du contenu du champ
     *
     * @param $len nouvelle valeur (0 pour désactiver la limitation)
     *
     * @return obj (le champ en question)
     */
    public function setMinLength($len)
    {
        $this->min_lenght = (is_numeric($len) AND (int) $len > 0) ? (int) $len : 0;

        return $this;
    }

    /**
     * Change la longueur maximale du contenu du champ
     *
     * @param $len nouvelle valeur (0 pour désactiver la limitation)
     *
     * @return obj (le champ en question)
     */
    public function setMaxLength($len)
    {
        if(is_numeric($len))
        {
            if((int) $len > 0)
                $this->attrs['maxlength'] = (int) $len;
            elseif((int) $len == 0)
                unset($this->attrs['maxlength']);
        }

        return $this;
    }

    public function isValid()
    {
        if(parent::isValid())
        {
            if(strlen($this->getValue()) < $this->min_lenght)
            {
                $this->_error('minlength', $this->min_lenght);
                return False;
            }
            
            if(!empty($this->attrs['maxlength']) AND strlen($this->getValue()) > $this->attrs['maxlength'])
            {
                $this->_error('maxlength', $this->attrs['maxlength']);
                return False;
            }

            return True;
        }

        return False;
    }
    
    public function toHTML()
    {
        $this->_makeClass();

        $attrs = $this->attrs;
        $attrs['value'] = htmlspecialchars(($this->canBound()) ? $attrs['value'] : '');

        $html = sprintf('<input %s/>', $attrs);

        return $html;
    }
}


//Champ de texte simple
class Field_Text extends FormInput {

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);
    }
    
    public function isValid()
    {
        if(parent::isValid())
        {
            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }
}


//Champ de soumission du formulaire
class Field_SubmitButton extends FormInput {

    public function __construct($name)
    {
        parent::__construct($name);
        $this->setValue($name);
        $this->required(False);

        $this->attrs['type'] = 'submit';
    }
}


//Champ de texte pour URL
class Field_URL extends FormInput {

    protected $verify_exists = False;

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);

        $this->setErrorText('invalid_url', 'L\'URL n\'est pas valide.');
    }

    /**
     * Si tourné à True, vérifiera la présence d'une page à l'url donnée
     * (on attendra une réponse 200 OK via le protocole HTTP)
     *
     * @param $bool vérification ou non
     *
     * @return obj (le champ en question)
     */
    public function verifyExists($bool)
    {
        $this->verify_exists = (bool) $bool;

        return $this;
    }

    public function isValid()
    {
        if(parent::isValid())
        {
            if(parse_url($this->getValue(), PHP_URL_SCHEME) === False)
            {
                $this->_error('invalid_url');
                return False;
            }

            if($this->verify_exists)
            {
                $headers = @get_headers($this->getValue());
                if(!$headers)
                {
                    $this->_error('invalid_url');
                    return False;
                }
                elseif(!in_array('HTTP/1.1 200 OK', $headers) AND !in_array('HTTP/1.0 200 OK', $headers))
                {
                    $this->_error('invalid_url');
                    return False;
                }
            }

            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }
}

//Champ décimal :: on attend un nombre (entier ou flottant)
class Field_Decimal extends FormInput {

    protected $min = Null;
    protected $max = Null;

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);

        $this->setErrorText('decimal_required', 'Doit être un nombre.');
        $this->setErrorText('lower_than_min_decimal', 'Ce nombre est trop petit (au moins %d).');
        $this->setErrorText('higher_than_max_decimal', 'Ce nombre est trop grand (au maximum %d).');
    }

    /**
     * Définit un minimum pour le champ
     *
     * @param $int valeur du minimum
     *
     * @return obj (le champ en question)
     */
    public function min($int)
    {
        $this->min = (is_numeric($int)) ? (int) $int : NULL;

        return $this;
    }

    /**
     * Définit un maximum pour le champ
     *
     * @param $int valeur du maximum
     *
     * @return obj (le champ en question)
     */
    public function max($int)
    {
        $this->max = (is_numeric($int)) ? (int) $int : NULL;

        return $this;
    }

    public function isValid()
    {
        if(parent::isValid())
        {
            $val = $this->getValue();
            
            if(!empty($val) OR $this->required)
            {
                if(!is_numeric($val))
                {
                    $this->_error('decimal_required');
                    return False;
                }

                if($this->min !== NULL AND $val < $this->min)
                {
                    $this->_error('lower_than_min_decimal', $this->min);
                    return False;
                }

                if($this->max !== NULL AND $val > $this->max)
                {
                    $this->_error('higher_than_max_decimal', $this->max);
                    return False;
                }
            }

            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }
        return False;
    }
}

//Textarea
class Field_Textarea extends FormInput {

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);
        
        $this->attrs['cols'] = '';
        $this->attrs['rows'] = '';
    }

    /**
     * Change la valeur de l'attribut cols
     *
     * @param $nb_cols nouvelle valeur (int, ou string représentant un pourcentage)
     *
     * @return obj (le champ en question)
     */
    public function cols($nb_cols)
    {
        $percent = (substr((string) $nb_cols, -1) == '%');
        $nb_cols = rtrim($nb_cols, '%');

        if(is_numeric($nb_cols) AND $nb_cols > 0)
            $this->attrs['cols'] = ($percent) ? $nb_cols.'%' : (int) $nb_cols;

        return $this;
    }

    /**
     * Change la valeur de l'attribut rows
     *
     * @param $nb_rows nouvelle valeur (int, ou string représentant un pourcentage)
     *
     * @return obj (le champ en question)
     */
    public function rows($nb_rows)
    {
        $percent = (substr((string) $nb_rows, -1) == '%');
        $nb_rows = rtrim($nb_rows, '%');

        if(is_numeric($nb_rows) AND $nb_rows > 0)
            $this->attrs['rows'] = ($percent) ? $nb_rows.'%' : (int) $nb_rows;

        return $this;
    }
    
    public function isValid()
    {
        if(parent::isValid())
        {
            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }
    
    public function toHTML()
    {
        $this->_makeClass();

        $attrs = $this->attrs;
        $value = ($this->canBound()) ? htmlspecialchars($attrs['value']) : '';
        unset($attrs['value']);
        unset($attrs['type']);

        $html = sprintf('<textarea %s>%s</textarea>', $attrs, $value);

        return $html;
    }
}


//Champ d'email
class Field_Email extends FormInput {

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);

        $this->setErrorText('invalid_email', 'L\'adresse email n\'est pas valide');
    }

    public function isValid()
    {
        if(parent::isValid())
        {
            if(filter_var($this->getValue(), FILTER_VALIDATE_EMAIL))
            {
                $value = $this->getValue();
                return empty($value) ? True : $this->passUserValidationRules();
            }

            $this->_error('invalid_email');
        }
        return False;
    }
}

//Champ caché
class Field_Hidden extends FormInput {

    public function __construct($name, $value)
    {
        parent::__construct($name);
        $this->attrs['type'] = 'hidden';
        $this->setValue($value);
    }
    
    public function isValid()
    {
        if(parent::isValid())
        {
            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }
        
        return False;
    }
}


//Champ de mot de passe
class Field_Password extends FormInput {

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->attrs['type'] = 'password';
        $this->setValue($value);
        $this->autoBound(False);
    }
    
    public function isValid()
    {
        if(parent::isValid())
        {
            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }
}


//Champ de texte pour date
class Field_Date extends FormInput {

    protected $format;

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);


        $this->setErrorText('invalid_format', 'Le format de référence est incorrect (%s).');
        $this->setErrorText('invalid_date', 'La date est invalide. Format à respecter : %s');
    }

    /**
     * Change le format de date utilisé
     *
     * @param $format voir http://fr.php.net/manual/fr/function.date.php pour les formats
     *
     * \warning Sous windows (strptime n'étant pas implémentée)
     *          seuls ces formats seront parsés %S, %M, %H, %d, %m, %Y
     *
     * @return obj (le champ en question)
     */
    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    public function isValid()
    {
        if(parent::isValid())
        {
            if(empty($this->format))
            {
                $this->_error('invalid_format', $this->format);
                return False;
            }

            if(strptime($this->getValue(), $this->format) === False)
            {
                $this->_error('invalid_date', $this->format);
                return False;
            }
            
            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }
}


//Champ checkbox
class Field_Bool extends FormField {

    public function __construct($name, $value=False, $label='')
    {
        parent::__construct($name, $label);
        $this->attrs['type'] = 'checkbox';
        $this->attrs['value'] = '1';

        if((bool) $value)
            $this->_checked(True);
    }

    /**
     * Coche ou décoche la checkbox
     *
     * @param $bool état de la checkbox
     *
     * @return obj (le champ en question)
     */
    private function _checked($bool)
    {
        if((bool) $bool)
            $this->attrs['checked'] = 'checked';
        else
            unset($this->attrs['checked']);
    }

    /**
     * Adapte setValue pour une checkbox, si la valeur en paramètre équivaut à True, on coche la checkbox
     *
     * @param $value état de la checkbox
     *
     * @return obj (le champ en question)
     */
    public function setValue($value)
    {
        $this->_checked((bool) $value);

        return $this;
    }

    /**
     * Adapte getValue pour une checkbox, si la valeur en paramètre 
     * équivaut à True, on coche la checkbox
     *
     * @param $value état de la checkbox
     *
     * @return bool
     */
    public function getValue()
    {
        return (isset($this->attrs['checked'])) ? 1 : 0;
    }
    
    public function isValid()
    {
        if(parent::isValid())
        {
            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }
    
    public function toHTML()
    {
        $this->_makeClass();

        $attrs = $this->attrs;

        if(!$this->canBound())
            unset($attrs['checked']);

        $html = sprintf('<input %s/>', $attrs);

        return $html;
    }
}


//Champ select
class Field_Select extends FormField {

    protected $options=array();
    protected $empty_opt_text='----------';

    public function __construct($name, $value='', $label='')
    {
        parent::__construct($name, $label);
        $this->setValue($value);
        
        $this->setErrorText('invalid_val', 'La valeur proposée est incorrecte.');
    }

    /**
     * Définit les d'options du select
     *
     * @param $array array contenant les options
     *               array(
     *                       'Europe' => array(
     *                                           'fr' => 'France',
     *                                           'es' => 'Espagne'
     *                                       ),
     *                       'clef' => 'Texte à afficher',
     *                   )
     *
     * @return obj (le champ en question)
     */
    public function options($array)
    {
        $this->options = $array;

        return $this;
    }
    
    /**
     * Définit le texte à afficher pour l'option vide
     * 
     * @param $text
     * 
     * @return $this
     */
    public function setEmptyOptText($text)
    {
        $this->empty_opt_text = $text;
        
        return $this;
    }

    /**
     * Lance la construction des options du select
     *
     * @return string (code html des options)
     */
    protected function _makeOptions()
    {
        $output = '<option value="">'.$this->empty_opt_text.'</option>';
        foreach($this->options as $name => $text)
        {
            $this->_proceedOptions($output, $name, $text);
        }

        return $output;
    }

    /**
     * Fonction s'occupant de générer du HTML pour les options
     *
     * @param &$output Référence vers la variable qui contiendra le html
     * @param $name valeur du <option>
     * @param $text texte à afficher pour l'option
     *
     * @return void
     */
    protected function _proceedOptions(&$output, $name, $text='')
    {
        if(is_array($text))
        {
            $output .= '<optgroup label="'.$name.'">';
            foreach($text as $key => $value)
            {
                $this->_proceedOptions($output, $key, $value);
            }
            $output .= '</optgroup>';
        }
        elseif(!empty($text))
        {
            $selected = ($name == $this->getValue() AND $this->canBound()) ? ' selected="selected"' : '';
            $output .= '<option value="'.$name.'"'.$selected.'>'.$text.'</option>';
        }
    }
    
    public function isValid()
    {
        if(parent::isValid())
        {
            if(!isset($this->options[$this->getValue()]))
            {
                $this->_error('invalid_val');
                return False;
            }

            $value = $this->getValue();
            return empty($value) ? True : $this->passUserValidationRules();
        }

        return False;
    }

    public function toHTML()
    {
        $this->_makeClass();
        $options = $this->_makeOptions();

        $attrs = $this->attrs;
        unset($attrs['value']);

        $html = sprintf('<select %s>%s</select>', $attrs, $options);

        return $html;
    }
}


//Champ d'upload
class Field_File extends FormInput {

    protected $form = Null;
    protected $valid_ext = array();
    protected $max_size = False;
    protected $expect_array = False; // sera à True si on attend plusieurs fichiers

    public function __construct($name, $label='', &$oForm=Null)
    {
        parent::__construct($name, $label);
        $this->attrs['type'] = 'file';
        $this->autoBound(False);
        
        if(substr($this->getName(), -2) == '[]')
            $this->expect_array = True;

        if(is_null($oForm) OR !($oForm instanceof Form))
            trigger_error('Une instance du formulaire doit être passée comme troisième paramètre au champ « '.$name.' ».', E_USER_ERROR);

        //on adapte le form
        $this->form = $oForm;
        $this->form->setEnctype('multipart/form-data');

        //définition des erreurs
        $this->setErrorText('invalid_ext', 'L\'extension du fichier n\'est pas valide. Extensions autorisées : %s');
        $this->setErrorText('too_big', 'Le fichier est trop volumineux (%d octets max)');
        $this->setErrorText('too_many_files', 'Trop de fichiers ont été soumis. Nombre maximal : %d');
        $this->setErrorText('not_enough_files', 'Pas assez de fichiers ont été soumis. Nombre minimal : %d');
    }

    /**
     * Fonction permettant d'autoriser des extensions
     *
     * @param $ext array contenant les extensions à autoriser
     *
     * @return obj (le champ en question)
     */
    public function addValidExtension(array $ext)
    {
        $this->valid_ext = array_merge($this->valid_ext, $ext);

        return $this;
    }

    /**
     * Fonction permettant de définir la taille maximale du fichier
     *
     * @param $size taille max
     *
     * @return obj (le champ en question)
     */
    public function setMaxSize($size)
    {
        if(!is_numeric($size) OR $size < 0)
            trigger_error('La taille maximale pour la champ « '.$this->getName().' » doit être un nombre strictement supérieur à zéro.', E_USER_ERROR);

        $this->form->add('Hidden', 'POST_MAX_SIZE')->value((int) $size);
        $this->max_size = (int) $size;

        return $this;
    }
    
    /**
     * Fonction permettant de définir le nombre minimal de fichiers à accepter
     * 
     * \note 0 pour désactiver la limite
     *
     * @param $nb Nombre min
     *
     * @return obj (le champ en question)
     */
    public function setMinFilesNb($nb)
    {
        return $this->setMinLength($nb);
    }
    
    /**
     * Fonction permettant de définir le nombre maximal de fichiers à accepter
     * 
     * \note 0 pour désactiver la limite
     *
     * @param $nb Nombre max
     *
     * @return obj (le champ en question)
     */
    public function setMaxFilesNb($nb)
    {
        return $this->setMaxLength($nb);
    }

    public function getValue()
    {
        $return = array();
        
        if(!$this->expect_array)
            $return = isset($_FILES[$this->getName()]) ? $_FILES[$this->getName()] : array();
        else
        {
            $this_name = substr($this->getName(), 0, -2);
            
            foreach($_FILES[$this_name]['name'] as $id => $name)
            {
                if(UPLOAD_ERR_NO_FILE == $_FILES[$this_name]['error'][$id])
                {
                    if($this->required)
                        $return[] = array();
                }
                else
                    $return[] = array(
                                    'name'      => $name,
                                    'type'      => $_FILES[$this_name]['type'][$id],
                                    'error'     => $_FILES[$this_name]['error'][$id],
                                    'tmp_name'  => $_FILES[$this_name]['tmp_name'][$id],
                                    'size'      => $_FILES[$this_name]['size'][$id]
                                );
            }
        }
        
        return $return;
    }

    public function isValid()
    {
        $files = (!$this->expect_array) ? array($this->getValue()) : $this->getValue();
        
        if(!empty($this->attrs['maxlenght']) AND count($files) > $this->attrs['maxlenght'])
        {
            $this->_error('too_many_files', $this->attrs['maxlenght']);
            return False;
        }
        
        if(!empty($this->attrs['minlenght']) AND count($files) > $this->attrs['minlenght'])
        {
            $this->_error('not_enough_files', $this->attrs['minlenght']);
            return False;
        }
        
        foreach($files as $file)
        {
            if(empty($file))
            {
                if(!$this->required)
                    continue;
                else
                {
                    $this->_error('required');
                    return False;
                }
            }
            
            if(!empty($this->valid_ext) AND !in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), $this->valid_ext))
            {
                $this->_error('invalid_ext', implode(', ', $this->valid_ext));
                return False;
            }

            if($this->max_size !== False AND $file['size'] > $this->max_size)
            {
                $this->_error('too_big', $this->max_size);
                return False;
            }
            
            if($file['error'] != UPLOAD_ERR_OK OR !is_uploaded_file($file['tmp_name']) OR 
               !$this->passUserValidationRules($file))
                return False;
        }
        
        return True;
    }
    
    /**
     * Indique si le formulaire passe les règles de validation personnalisées
     * 
     * \note Version modifiée pour les envois de fichier (de manière à 
     *       gérer les uploads de plusieurs fichiers simultanément via
     *       le même champ)
     *
     * @return bool
     */
    protected function passUserValidationRules($file='')
    {
        foreach($this->user_validation_rules as $key => $rule)
        {
            $pass = call_user_func($rule, $file, $this->form_ref);
            
            if(!empty($pass))
            {
                $this->_userError($pass);
                return False;
            }
        }
        
        return True;
    }
}


/**
*
*   Class "accessoires" (ListArray et AttributeList trouvées je ne sais où sur l'immensité de la toile ...)
*
**/
class ListArray implements Iterator, ArrayAccess {

    protected $array = array();
    private $valid = false;

    function __construct(Array $array = array()) {
        $this->array = $array;
    }

    /* Iterator */
    function rewind()  { $this->valid = (FALSE !== reset($this->array)); }
    function current() { return current($this->array);      }
    function key()     { return key($this->array);  }
    function next()    { $this->valid = (FALSE !== next($this->array));  }
    function valid()   { return $this->valid;  }

    /* ArrayAccess */
    public function offsetExists($offset) {
        return isset($this->array[$offset]);
    }
    public function offsetGet($offset) {
        return $this->array[$offset];
    }
    public function offsetSet($offset, $value) {
        return $this->array[$offset] = $value;
    }
    public function offsetUnset($offset) {
        unset($this->array[$offset]);
    }
}


class AttributeList extends ListArray {

    public function __toString() {
        $output = '';
        if (!empty($this->array)) {
            foreach($this->array as $a => $v) {
                $output .= sprintf('%s="%s" ', $a, $v);
            }
        }
        return $output;
    }
}

//trouvé ici :: http://fr.php.net/manual/fr/function.strptime.php#86572
/**
 * Parse a time/date generated with strftime().
 *
 * This function is the same as the original one defined by PHP (Linux/Unix only),
 *  but now you can use it on Windows too.
 *  Limitation : Only this format can be parsed %S, %M, %H, %d, %m, %Y
 *
 * @author Lionel SAURON
 * @version 1.0
 * @public
 *
 * @param $sDate(string)    The string to parse (e.g. returned from strftime()).
 * @param $sFormat(string)  The format used in date  (e.g. the same as used in strftime()).
 * @return (array)          Returns an array with the <code>$sDate</code> parsed, or <code>false</code> on error.
 */
if(function_exists("strptime") == false)
{
    function strptime($sDate, $sFormat)
    {
        $aResult = array
        (
            'tm_sec'   => 0,
            'tm_min'   => 0,
            'tm_hour'  => 0,
            'tm_mday'  => 1,
            'tm_mon'   => 0,
            'tm_year'  => 0,
            'tm_wday'  => 0,
            'tm_yday'  => 0,
            'unparsed' => $sDate,
        );

        while($sFormat != "")
        {
            // ===== Search a %x element, Check the static string before the %x =====
            $nIdxFound = strpos($sFormat, '%');
            if($nIdxFound === false)
            {

                // There is no more format. Check the last static string.
                $aResult['unparsed'] = ($sFormat == $sDate) ? "" : $sDate;
                break;
            }

            $sFormatBefore = substr($sFormat, 0, $nIdxFound);
            $sDateBefore   = substr($sDate,   0, $nIdxFound);

            if($sFormatBefore != $sDateBefore) break;

            // ===== Read the value of the %x found =====
            $sFormat = substr($sFormat, $nIdxFound);
            $sDate   = substr($sDate,   $nIdxFound);

            $aResult['unparsed'] = $sDate;

            $sFormatCurrent = substr($sFormat, 0, 2);
            $sFormatAfter   = substr($sFormat, 2);

            $nValue = -1;
            $sDateAfter = "";
            switch($sFormatCurrent)
            {
                case '%S': // Seconds after the minute (0-59)

                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if(($nValue < 0) || ($nValue > 59)) return false;

                    $aResult['tm_sec']  = $nValue;
                    break;

                // ----------
                case '%M': // Minutes after the hour (0-59)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if(($nValue < 0) || ($nValue > 59)) return false;

                    $aResult['tm_min']  = $nValue;
                    break;

                // ----------
                case '%H': // Hour since midnight (0-23)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if(($nValue < 0) || ($nValue > 23)) return false;

                    $aResult['tm_hour']  = $nValue;
                    break;

                // ----------
                case '%d': // Day of the month (1-31)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if(($nValue < 1) || ($nValue > 31)) return false;

                    $aResult['tm_mday']  = $nValue;
                    break;

                // ----------
                case '%m': // Months since January (0-11)
                    sscanf($sDate, "%2d%[^\\n]", $nValue, $sDateAfter);

                    if(($nValue < 1) || ($nValue > 12)) return false;

                    $aResult['tm_mon']  = ($nValue - 1);
                    break;

                // ----------
                case '%Y': // Years since 1900
                    sscanf($sDate, "%4d%[^\\n]", $nValue, $sDateAfter);

                    if($nValue < 1900) return false;

                    $aResult['tm_year']  = ($nValue - 1900);
                    break;

                // ----------
                default: break 2; // Break Switch and while
            }

            // ===== Next please =====
            $sFormat = $sFormatAfter;
            $sDate   = $sDateAfter;

            $aResult['unparsed'] = $sDate;

        } // END while($sFormat != "")


        // ===== Create the other value of the result array =====
        $nParsedDateTimestamp = mktime($aResult['tm_hour'], $aResult['tm_min'], $aResult['tm_sec'],
                                $aResult['tm_mon'] + 1, $aResult['tm_mday'], $aResult['tm_year'] + 1900);

        // Before PHP 5.1 return -1 when error
        if(($nParsedDateTimestamp === false)
        ||($nParsedDateTimestamp === -1)) return false;

        $aResult['tm_wday'] = (int) strftime("%w", $nParsedDateTimestamp); // Days since Sunday (0-6)
        $aResult['tm_yday'] = (strftime("%j", $nParsedDateTimestamp) - 1); // Days since January 1 (0-365)

        return $aResult;
    } // END of function

} // END if(function_exists("strptime") == false)

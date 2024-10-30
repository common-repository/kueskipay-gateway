<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Register all actions and filters for the plugin.
 **/

class Kueski_Gateway_Loader
{
    /**
     * The array of actions registered with WordPress.
     *
     * @access   protected
     * @var      array    $actions
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @access   protected
     * @var      array    $filters 
     */
    protected $filters;

    public function __construct()
    {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param    string               $hook             
     * @param    object               $component        
     * @param    string               $callback         
     * @param    int                  $priority         
     * @param    int                  $accepted_args    
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param    string               $hook             
     * @param    object               $component        
     * @param    string               $callback         
     * @param    int                  $priority         
     * @param    int                  $accepted_args    
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Register the actions and hooks into a single collection.
     * 
     * @access   private
     * @param    array                $hooks            
     * @param    string               $hook             
     * @param    object               $component        
     * @param    string               $callback         
     * @param    int                  $priority         
     * @param    int                  $accepted_args    
     * @return   array                                  
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {

        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}

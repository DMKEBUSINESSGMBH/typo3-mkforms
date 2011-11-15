/**
 * @author Pawel Gasiorowski <p.gasiorowski@axent.pl>
 * @package Axent.DragDropTree
 * @license MIT
 * @url http://weblog.axent.pl/examples/js.drag-drop-tree/
 * @version 1.3
 */
AxentTree = Class.create({
	oCurrentActiveNode: null,
    options : {},
    serialized : [],
    aObserved : [],
    initialize: function(element) {                                
        this.element = $(element);                                              // Tree container element, usually UL tag
        this.options = Object.extend({                                        
            isDraggable : false,                                                 // Enables / disables dragging tree nodes
            isDroppable : false,                                                 // Enables / disables dropping elements on tree nodes
            iconsFolder : 'img/',                                               // Path to icons folder
            plusIcon : 'plus.gif',                                              // Plus icon image
            minusIcon : 'minus.gif',                                            // Minus icon image
            addFolderIcon : true,                                               // Enables / disables adding folder icon to tree nodes
            folderIcon : 'folder.gif',                                          // Folder icon image
            treeClass : 'jstree',
            treeNodeClass : 'jstree-node',
            treeNodeClassActive : 'jstree-node-active',
            treeNodePlusClass : 'jstree-node-plus',
            treeNodeDropOnClass : 'jstree-node-dropon',
            treeNodeDropAfterClass : 'jstree-node-dropafter',
            treeNodeHandleClass : 'jstree-node-handle',
            beforeDropNode : null,                                              // Callback function before node is dropped (return false to cancel drop)
            afterDropNode : null,                                               // Callback function after node is dropped
            allowDropAfter : true,                                              // Enables / disables dropping nodes after spcefic nodes (disable when application does not need / allow reordering nodes)
			nodeclick_handler: null,
			nodeopen_handler: null,
			nodeclose_handler: null,
			leafclick_handler: null,
            dropAfterOverlap : 0.95
        }, arguments[1] || {} );
        
        this.element.addClassName(this.options.treeClass);
		Array.from(this.element.childNodes).each(function(oNode) {
			oNode = $(oNode);
			if(oNode.nodeName != undefined && oNode.nodeName == "LI") {
				if((oUl = oNode.down("ul"))) {
					this.initUlIfNeeded(oUl);
				}
				
				this.initializeTreeNode(oNode);
			}
		}.bind(this));
        
        /**
         *  Add serializeTree method to tree container element
         */                                 
        Object.extend(this.element,{
            serializeTree : function (inputName) {
                var serialized = $H();
                    if (inputName) {
                        serialized.set('inputName',inputName);
                    } else {
                        serialized.set('inputName','data[Node]');
                    }
                this.select('li').each(function(node){
                    var data = {};
                        data.id = node.identify();
    				    data.parent_id = (node.up('li') != undefined) ? node.up('li').identify() : '';
    				    data.previous_id = (node.previous('li') != undefined) ? node.previous('li').identify() : '';
    				this.set(this.get('inputName')+'['+node.identify()+'][id]',data.id);
    				this.set(this.get('inputName')+'['+node.identify()+'][parent_id]',data.parent_id);
    				this.set(this.get('inputName')+'['+node.identify()+'][previous_id]',data.previous_id);
                },serialized);
                serialized.unset('inputName');                        
                return serialized.toQueryString();
            }
        });
    },
    /**
     *  Show hide node's children
     */                                 
    showHideNode : function (event) {
        li = Event.element(event).up('li');
		this.toggleNode(li);
    },
	toggleNode: function(oLi) {
		ul = oLi.down('ul');
		
        if (ul != undefined) {
			if(ul.visible()) {
				this.closeNode(oLi);
			} else {
				this.openNode(oLi);
			}
			
            oLi.down("img").src = (ul.visible()) ? (this.options.iconsFolder+this.options.minusIcon) : (this.options.iconsFolder+this.options.plusIcon);
        }
	},
	initUlIfNeeded: function(oUl) {
		Array.from(oUl.childNodes).each(function(oSubNode) {
			oSubNode = $(oSubNode);
			if(oSubNode.nodeName != undefined && oSubNode.nodeName == "LI" && !oSubNode.hasClassName(this.options.treeNodeClass)) {
				this.initializeTreeNode(oSubNode);
			}
		}.bind(this));
	},
	openNode: function(oLi) {
		if((oUl = oLi.down("UL"))) {	
			this.initUlIfNeeded(oUl);
			Element.show(oUl);
			Element.show(oLi.up("UL"));
		}
		
		oCurrent = oLi;
		while((oParentUl = oCurrent.up("ul")) && oParentUl.id != this.element.id) {
			oCurrent = oParentUl.up("li");
			this.initUlIfNeeded(oParentUl);
			Element.show(oParentUl);
			Element.show(oLi.up("UL"));
		}
		
		if(this.options.nodeopen_handler) {
			this.options.nodeopen_handler(this.getValueForNode(oLi));
		}
	},
	closeNode: function(oLi) {
		Element.hide(oLi.down('ul'));
		if(this.options.nodeclose_handler) {
			this.options.nodeclose_handler(this.getValueForNode(oLi));
		}
	},
	nodeClick: function(event) {
		li = Event.element(event).up('li');
		this.setNodeActive(li);
		value = this.getValueForNode(li);
		
		if(this.options.nodeclick_handler) {
			this.options.nodeclick_handler(value);
		}
	},
	setNodeActive: function(oLi) {
		if(this.oCurrentActiveNode) {
			this.oCurrentActiveNode.down("span").removeClassName(this.options.treeNodeClassActive);
		}
		
		this.oCurrentActiveNode = oLi;
		
		oLi.down("span").addClassName(this.options.treeNodeClassActive);
		this.openNode(oLi);
	},
	getSelectedValue: function() {
		if(this.oCurrentActiveNode) {
			return this.getValueForNode(this.oCurrentActiveNode);
		}
		
		return false;
	},
	setValue: function(iValue) {
		this.element.select("li").each(function(node) {
			if(this.getValueForNode(node) == iValue) {
				
				this.setNodeActive(node);
				throw $break;
			}
		}.bind(this));
	},
	getValueForNode: function(oNode) {
		return oNode.down('span').down('input').value;
	},
	getSelectedLabel: function() {
		if(this.oCurrentActiveNode) {
			return this.getLabelForNode(this.oCurrentActiveNode);
		}
		
		return "";
	},
	getLabelForNode: function(oNode) {
		oSpan = oNode.down('span');
		
		if(typeof oSpan.innerText == "undefined") {
			return oSpan.textContent;
		}
		
		return oSpan.innerText;
	},
	getSelectedPath: function() {
		if(this.oCurrentActiveNode) {
			return this.getPathForNode(this.oCurrentActiveNode);
		}
		
		return "";
	},
	getPathForNode: function(oNode) {
		aSegments = [];
		oCurrent = oNode;
		while((oParentUl = oCurrent.up("ul")) && oParentUl.id != this.element.id) {
			oCurrent = oParentUl.up("li");
			aSegments.push(this.getLabelForNode(oCurrent));
		}
		
		return aSegments.join("/") + "/";
	},
	unloadHandlers: function() {
		this.aObserved.each(function(oObj) {
			Event.stopObserving(oObj);
		});
		this.aObserved = [];
	},
    onHoverNode : function (node,dropOnNode,overlap) {
        if (this.options.allowDropAfter) {
            if (overlap > this.options.dropAfterOverlap) {
                dropOnNode.addClassName(this.config.treeNodeDropAfterClass);
            } else {
                dropOnNode.removeClassName(this.config.treeNodeDropAfterClass);
            }
        }
    },
    /**
     *  Droappable.onDrop callback 
     */                                 
    onDropNode : function (node,dropOnNode,point) {
        if (typeof this.options.beforeDropNode == 'function') {
            node.hide();
            var ret = this.options.beforeDropNode(node,dropOnNode,point);
            node.show();
            if (ret === true || ret === false) {
                return ret;
            }
        }
        
        sourceNode = node.up('li');
        
        /**
         *  Insert after dropOnNode
         */                                 
        if (dropOnNode.hasClassName(this.config.treeNodeDropAfterClass)) {
            dropOnNode.insert({after:node});
            dropOnNodeParent = dropOnNode.up('li',1)
            if (dropOnNodeParent != undefined) {
                dropOnNodePlus = dropOnNodeParent.down('img.'+this.options.treeNodePlusClass);
                dropOnNodePlus.src = this.options.iconsFolder+this.options.minusIcon;
                dropOnNodePlus.setStyle({visibility:'visible'});
            }
        }
        /**
         *  Insert under dropOnNode
         */
        else {
            ul = dropOnNode.down('ul',0);                                                   
            if (ul == undefined) {
                ul = new Element('ul');
                dropOnNode.insert(ul);
            }
            ul.show();
            ul.insert(node);
            dropOnNodePlus = dropOnNode.down('img.'+this.options.treeNodePlusClass);
            dropOnNodePlus.src = this.options.iconsFolder+this.options.minusIcon;
            dropOnNodePlus.setStyle({visibility:'visible'});
        }
        
        if (sourceNode != undefined) {
            sourceNodePlus = sourceNode.down('img.'+this.options.treeNodePlusClass);
            if (sourceNode.down('li') == undefined) {
                sourceNodePlus.setStyle({visibility:'hidden'});
            }
        }
        
        if (typeof this.options.afterDropNode == 'function') {
            var ret = this.options.afterDropNode(node,dropOnNode,point);
            if (ret === true || ret === false) {
                return ret;
            }
        }
    },
    initializeTreeNode : function (li) {
		li.addClassName(this.options.treeNodeClass);
		
		if(li.up('ul').id != this.element.id) {
			// not the root element
			if(li.down('ul')) {
				li.down('ul').hide();
			}
		}
		
		// Insert folder icon at the top of li element
		
		if(this.options.addFolderIcon) {
			oFolder = new Element('img', {
				src : this.options.iconsFolder+this.options.folderIcon,
				className : this.options.treeNodeHandleClass
			});
			this.eventObserve(oFolder, 'click', this.nodeClick.bindAsEventListener(this));
			li.insert({top : oFolder});
        }

        liPlus = new Element('img',{
            src:this.options.iconsFolder+this.options.minusIcon,
            className:this.options.treeNodePlusClass
        });
        if (li.down('li') == undefined) {
            liPlus.setStyle({visibility:'hidden'});
        } else if (li.down('ul').visible() === false) {
            liPlus.src = this.options.iconsFolder+this.options.plusIcon;
        }




		
		this.eventObserve(li.down('span'), 'click', this.nodeClick.bindAsEventListener(this));
/*		if(li.down('li') != undefined && li.down('ul').visible() === false) {
			liPlus = new Element('img',{
				src:this.options.iconsFolder+this.options.minusIcon,
				className:this.options.treeNodePlusClass
			});
			
			liPlus.src = this.options.iconsFolder + this.options.plusIcon;
			this.eventObserve(liPlus,'click',this.showHideNode.bindAsEventListener(this));
			li.insert({top:liPlus});
		}*/
		
		liPlus = new Element('img',{
            src:this.options.iconsFolder+this.options.minusIcon,
            className:this.options.treeNodePlusClass
        });
        if (li.down('li') == undefined) {
            liPlus.setStyle({visibility:'hidden'});
        } else if (li.down('ul').visible() === false) {
            liPlus.src = this.options.iconsFolder+this.options.plusIcon;
        }

		this.eventObserve(liPlus,'click',this.showHideNode.bindAsEventListener(this));
		li.insert({top:liPlus});
		
		// Make node draggable
        if(this.options.isDraggable) {                                         
            new Draggable(li,{handle:this.options.treeNodeHandleClass,revert:true,starteffect:null});
        }

		// Make node droppable
        if (this.options.isDroppable) {
            Droppables.add(li, {
                accept:this.options.treeNodeClass,
                hoverclass:this.options.treeNodeDropOnClass,
                onDrop:this.onDropNode.bind(this),
                overlap:'horizontal',
                onHover:this.onHoverNode.bind(this)
            });
        }
    },
	eventObserve: function(oObj, sEvent, fFunc) {
		this.aObserved.push(oObj);
		Event.observe(
			oObj,
			sEvent,
			fFunc
		);
	}
});
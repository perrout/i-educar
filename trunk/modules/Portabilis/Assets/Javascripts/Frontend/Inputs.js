// simple search input

var defaultJQueryAutocompleteOptions = {
  minLength   : 1,
  autoFocus   : true,
  source      : function(request, response) { return simpleSearch.search(this.element, request, response) },
  select      : function(event, ui) { return simpleSearch.handleSelect(event, ui) },

  // options that can be overwritten
  // change      : function (event, ui) {},
  // close      : function (event, ui) {},
};


var defaultSimpleSearchOptions = {
  // options that cannot be overwritten

  get       : function(optionName) { return optionsUtils.get(this, optionName) },
  mergeWith : function(options) {
    options                     = optionsUtils.merge(this, options);
    options.autocompleteOptions = optionsUtils.merge(defaultJQueryAutocompleteOptions, options.autocompleteOptions);

    return options;
  },

  // options that must be overwritten
  objectName    : undefined,
  attrName      : undefined,
  searchPath    : undefined,
};


var simpleSearch = {

  /* API returns result as {value : label, value2, label2}
     but jquery autocomplete, expects [{value : label}, {value : label}]
  */
  fixResult :  function(result) {
    var fixed = [];

    $j.each(result, function(value, label) {
      fixed.push({ value : value, label : label})
    });

    return fixed;
  },

  search : function(element, request, response) {
    var $element      = $j(element);
    var hiddenInputId = $element.data('simple_search_options').hidden_input_id;
    var searchPath    = $element.data('simple_search_options').search_path;

    // clear the hidden id, because it will be set when the user select another result.
    $j(hiddenInputId).val('');

    $j.get(searchPath, { query : request.term }, function(dataResponse) {
      simpleSearch.handleSearch(dataResponse, response);
    });
  },

  handleSearch : function(dataResponse, response) {
    handleMessages(dataResponse['msgs']);

    if (dataResponse.result) {
      response(simpleSearch.fixResult(dataResponse.result));
    }
  },

  handleSelect : function(event, ui) {
    var $element      = $j(event.target);
    var hiddenInputId = $element.data('simple_search_options').hidden_input_id;

    $element.val(ui.item.label);
    $j(hiddenInputId).val(ui.item.value);

    return false;
  },

  validatesRequiredFields : function() {
    $j('input[type=hidden].simple-search-id.obrigatorio').each(function(index, element){
      var $element = $j(element);

      if (! $element.val())
        $j(buildId($element.data('for'))).val('');
    });
  },

  for : function(options) {
    options           = defaultSimpleSearchOptions.mergeWith(options);

    var inputId       = buildId(options.get('objectName') + '_' + options.get('attrName'));
    var hiddenInputId = buildId(options.get('objectName') + '_id');

    var $input        = $j(inputId);
    var $hiddenInput  = $j(hiddenInputId);

    $hiddenInput.addClass('simple-search-id');

    if ($input.hasClass('obrigatorio'))
      $hiddenInput.addClass('obrigatorio required');

    $input.data('simple_search_options', { 'hidden_input_id' : hiddenInputId, 'search_path' : options.get('searchPath') });
    $hiddenInput.attr('data-for', $input.attr('id'));

    $input.autocomplete(options.get('autocompleteOptions'));
  }
};

var simpleSearchHelper = {
  setup : function(objectName, attrName, searchPath, simpleSearchResourceOptions) {
    var defaultOptions = {
      searchPath : searchPath,
      objectName : objectName,
      attrName   : attrName,
    };

    var options = optionsUtils.merge(defaultOptions, simpleSearchResourceOptions);
    simpleSearch.for(options);
  },
};
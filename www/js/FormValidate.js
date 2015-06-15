/**
 * Created by User on 18.04.2015.
 * validation forms javascript
 *
 * @param id - id form
 * @param fraimWork - name fraimwork for add class error
 * @constructor
 */


function FormValidate(id,fraimWork){

    var that = this;

    that.id = id;

    that.fraimWork = fraimWork || undefined;

    /**
     * @param formObj - object jQuery form
     */
    that.formObj = jQuery('form#'+id);

    /**
     *  @param classRequired - name class for validate
     */
    that.classRequired = "required";

    /**
     * additional data for ajax
     */
    that.dataSubmit = {ajax_submit:1};

    /**
     *  return of name element
     */
    that.getElement = function(name){
        var el = that.formObj.find('[name="'+name+'"]');
        if(el.length == 0)
        {
            var new_name='';
            var name_arr = name.split('[');
            var l = name_arr.length;
            for(var i=0; i<l; i++)
            {
                name_arr[i] = name_arr[i].replace(']', '');
                if(i==0)new_name+=name_arr[i];
                else
                {
                    var anum=/(^\d+$)|(^\d+\.\d+$)/
                    if (anum.test(name_arr[i]))
                    {
                        el = that.formObj.find('[name="'+new_name+'[]"]:eq('+name_arr[i]+')');
                        if(el.length>0) break;
                    }
                    new_name+="["+name_arr[i]+"]"
                }
                el = that.formObj.find('[name="'+new_name+'"]');
                if(el.length>0) break;
            }
        }
        return el;

    }

    /**
     * output result validation after ajax
     * @param res
     */
    that.outputResult = function(res){
        is_success = parseInt(res.IS_SUCCESS);
        if (is_success)
        {
            that.clearOutputResult;
            that.resetForm;
            that.success(res);
        }
        else
        {
            that.clearOutputResult;
            that.resetForm;
            for (var key in res.ERROR_FIELDS) {
                el = that.getElement(key);
                that.setError(el);
            }
        }
    }

    /**
     * метод выполняющийся в случае успешной отправки формы
     */
    that.success = function(res){}

    /**
     *  clear result validation
     */
    that.clearOutputResult = function()
    {
        switch (that.fraimWork)
        {
            case 'bootstrap':
                that.formObj.find('has-error').removeClass('has-error');
                break;
            default:
                that.formObj.find('.error').removeClass('error');
        }
        console.log(that.formObj.find('has-error'));
    }

    /**
     * сброс формы
     */
    that.resetForm = function()
    {
        that.formObj.resetForm();
    }

    /**
     *  form submit
     */
    that.formObj.submit(function(){

        action = that.formObj.attr('action');
        if (!action) action = window.location.href;
        action += "?rand" + Math.random();

        var defOptions = {
            data: that.dataSubmit,
            dataType: 'json',
            url: action,
            beforeSubmit: function(formData){
                that.validate(formData);
            },

            success: function(res){
                that.outputResult(res);
            }
        }
        var opt = jQuery.extend( {}, defOptions, that.ajaxOptions );
        that.formObj.ajaxSubmit(opt);
        return false;
    });

    /**
     * cnt validation
     * @param formData
     */
    that.validate = function(formData){
        that.formObj.find('.error').removeClass('error');
        var err = 0;
        for (var i = 0; i<formData.length; i++)
        {
            var el = that.formObj.find('[name="'+formData[i].name+'"]');
            if (el.hasClass('empty')) err += that.validEmpty(el);
            if (el.hasClass('email')) err += that.validEmail(el);
        }
        if (err>0) return true;
        else return false;
    }

    /**
     *  add class "error" for element's form
     *  @param el = object date form
     */
    that.setError = function(el){
        if (typeof that.fraimWork == 'undefined')
            el.addClass('error');
        else if(that.fraimWork == 'bootstrap')
            el.closest('.form-group').addClass('has-error');
    }

    /**
     * valid empty
     */
    that.validEmpty = function(el){
        if (el.val() == '')
        {
            that.setError(el);
            return 1;
        }
        else return 0;
    }

    /**
     * valid email
     */
    that.validEmail = function(el){

        if (!el.val().match(/..+@.+\..+/))
        {
            that.setError(el);
            return 1;
        }
        else return 0;
    }
}

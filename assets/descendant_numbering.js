
function CollectParameters(container_selector)
{
    var params = {};
    
    jQuery.each(jQuery(container_selector).find("input,select,textarea"),function(idx,obj){
        var param_value = null;
        var jq_obj = jQuery(obj);
        
        if(jq_obj.is("input[type='checkbox']"))
        {
            param_value = jq_obj.is(":checked") ? 1 : 0;
        }
        else if(jq_obj.is("input[type='radio']")){
            
            if(jq_obj.is(":checked"))
                param_value = jq_obj.val();
        }
        else
            param_value = jq_obj.val();
        
        if(param_value !== null)
            params[jq_obj.attr("name")] = param_value;

    });
    
    return params;
}

function CollectCustomNumberingParameters(numbering_style_name)
{
    return CollectParameters("#"+numbering_style_name+"-parameters");
}

function RestoreParamsFromLocalStorage(var_name, container_selector)
{
    var params_str = localStorage[var_name];
    var params = params_str ? JSON.parse(params_str) : null;
    
    if(params)for(var param_name in params){
        
        var param_value  = params[param_name];
        
        if(!param_value)
            continue;

        var control = jQuery(container_selector+" [name='"+param_name+"']");
        
        if(control.is("input[type='checkbox']"))
            control.prop("checked",param_value);
        else if(control.is("input[type='radio']"))
            control.filter("[value='"+param_value+"']").prop("checked",true);
        else control.val(param_value);
    }
}

function RestoreCommonParams()
{
    RestoreParamsFromLocalStorage("descendant-numbering-parameters", "#common-numbering-parameters");
}

function RestoreCustomNumberingParamsFromLocalStorage(numbering_style_name)
{
    RestoreParamsFromLocalStorage(numbering_style_name+"-parameters", "#"+numbering_style_name+"-parameters");
}

function RestoreAllCustomNumberingParamsFromLocalStorage()
{
    jQuery.each(jQuery(".custom-descendant-numbering-parameters"),function(){
        RestoreCustomNumberingParamsFromLocalStorage(jQuery(this).data("numbering"));
    });
}

function SaveCommonParams()
{
    var common_params = CollectParameters("#common-numbering-parameters");
    localStorage["descendant-numbering-parameters"] = JSON.stringify(common_params);
}

function SaveCustomNumberingParameters(numbering_style_name)
{
    var params = CollectCustomNumberingParameters(numbering_style_name);
    localStorage[numbering_style_name+"-parameters"] = JSON.stringify(params);
    
}

function CollectPostData()
{
    var post_data = CollectParameters("#common-numbering-parameters");
    post_data["parameters"] = CollectCustomNumberingParameters(jQuery("#numbering-styles").val());
    
    return post_data;
}
function OnDownloadClick(event)
{

    var post_data = CollectPostData();
    
    post_data["download"] = true;
    post_data["dl-format"] = jQuery(event.target).data("dl-format");
    var form = jQuery("<form action='modules_v3/descendant_numbering/getdescendantnumbering.php' method='post' style='display: none;'></form>");
    for(var name in post_data)
    {
        var value = post_data[name];
        var input = jQuery("<input type='hidden'>");
        input.attr("name", name).attr("value", name === 'parameters' ? JSON.stringify(value) : value);
        
        input.appendTo(form);
        
    }
    form.appendTo("body");
    form.submit();
    form.remove();
    //window.location = "modules_v3/descendant_numbering/getdescendantnumbering.php?"+jQuery.param(post_data);
    
}

function OnPreviewClick()
{
    var post_data = CollectPostData();
    
    jQuery.post("modules_v3/descendant_numbering/getdescendantnumbering.php",post_data,function(data){
        if("error" in data)
            alert(data["error"]["message"]);
        else if("numbering" in data)
        {
            jQuery("#numbering-preview").show();
            jQuery("#numbering-preview-numbering-style").text(data["numberingClass"]["name"]);
            
            var tbody = jQuery("#numbering-preview tbody").empty();
            var null_spouses_tbody = jQuery("#null-spouses tbody");
            null_spouses_tbody.empty();
            var show_null_spouse_data = false;
            for(var indi in data["numbering"])
            {
                if(indi.indexOf("SPOUSE-NULL") === 0 ) //null spouse entry
                {
                    var null_spouse_data = indi.split("-");
                    null_spouses_tbody.append(
                            "<tr>"
                            +"<td class='facts_value'>"+"<a href='individual.php?pid="+null_spouse_data[2]+"&amp;ged="+null_spouse_data[4]+"' target='_blank'><i class='icon-indis'></i>"+null_spouse_data[2]+"</a>"+"</td>"
                            +"<td class='facts_value'>"+"<a href='family.php?famid="+null_spouse_data[3]+"&amp;ged="+null_spouse_data[5]+"' target='_blank'><i class='icon-sfamily'></i>"+null_spouse_data[3]+"</a>"+"</td>"
                            +"<td class='facts_value'>"+data["numbering"][indi]["number"]+"</td>"
                            +"</tr>");
                    
                    show_null_spouse_data = true;
                    
                    continue;
                }
                tbody.append("<tr><td class='facts_value'>"
                        +"<a href='individual.php?pid="+indi+"' target='_blank'><i class='icon-indis'></i>"+indi+"</a>"
                        +"<td class='facts_value'><a href='individual.php?pid="+indi+"' target='_blank'>"+data["numbering"][indi]["name"]+"</a>"
                        +"</td><td class='facts_value'>"
                        +data["numbering"][indi]["number"]
                        +"</td></tr>");
            }
            
            if(show_null_spouse_data)
                jQuery("#null-spouses").show();
            else 
                jQuery("#null-spouses").hide();
        }
    }).fail(function(jqXHR, textStatus, errorThrown){
        alert(errorThrown+" (error: "+textStatus+")");
    });
    
     
}

function OnNumberingParameterChanged(event)
{

    var parent_fieldset = jQuery(event.target).parents(".custom-descendant-numbering-parameters");
    
    var numbering_class = parent_fieldset.data("numbering");
    
    SaveCustomNumberingParameters(numbering_class);
    SaveCommonParams();
}

function OnNumberingClassSelected(event)
{

    localStorage.descendantNumberingClass = jQuery(event.target).val();
    jQuery(".custom-descendant-numbering-parameters").hide();
    jQuery("#"+localStorage.descendantNumberingClass+"-parameters").show();
    
}

jQuery(".desc-num-download").click(OnDownloadClick);
jQuery("#preview-numbering").click(OnPreviewClick);

if(localStorage.descendantNumberingClass)
    jQuery("#numbering-styles").val(localStorage.descendantNumberingClass);

jQuery("#numbering-styles").change(OnNumberingClassSelected);

jQuery("#numbering-preview, .custom-descendant-numbering-parameters, #null-spouses").hide();

jQuery("#numbering-styles").trigger("change");

jQuery(".custom-descendant-numbering-parameters, #common-numbering-parameters").find("input,select,textarea").change(OnNumberingParameterChanged);

RestoreAllCustomNumberingParamsFromLocalStorage();
RestoreCommonParams();




{if isset($categoryProducts) && count($categoryProducts) > 0 && $categoryProducts !== false}
<div class="clearfix blockproductscategory">
	<h2 class="productscategory_h2">
		{if $categoryProducts|@count == 1}
			{l s='%s other product in the same category:' sprintf=[$categoryProducts|@count] mod='productscategory'}
		{else}
			{l s='%s other products in the same category:' sprintf=[$categoryProducts|@count] mod='productscategory'}
		{/if}
	</h2>

	<div class="products" id="productscategory_list">
		{foreach from=$categoryProducts item='categoryProduct' name=categoryProduct}
			{include file='catalog/_partials/miniatures/product.tpl' product=$categoryProduct}
		{/foreach}
	</div>
</div>
{/if}

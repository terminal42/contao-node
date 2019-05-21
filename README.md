# Node bundle for Contao Open Source CMS

This bundle provides a new way for Contao to manage content elements that are common for multiple pages.

![](docs/preview.png)

## Installation

Install the bundle via Composer:

```
composer require terminal42/contao-node
```

## Configuration

Once installed, you can start creating groups and nodes in the `Content > Nodes` backend module. Roughly said they work
similar to articles – each node can have multiple content elements. 

You can then display those nodes in the front end using either the `Nodes` front end module or content element.
Both of them allow you to select either one or multiple nodes and order them.  

To better organize nodes you can specify the languages the respective content elements were written in and use arbitrary
tags to be able to quickly filter and find them again. Both the languages as well as the tags don't have any influence
on the front end but can help you to manage your content in an efficient way.

Thanks to the Contao picker, finding the correct node is as easy as it can get!

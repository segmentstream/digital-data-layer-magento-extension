# Digital Data Layer Magento Extension

Digital Data Layer streamlines the process of passing values between marketing tags (or your Tag Management container) and your pages, whilst future-proofing against updates and changes. We recommend creating the relevant JavaScript object on your page prior to deploying any marketing tags or Tag Management container script. Doing this will assure that all values will be present on the page when the script runs and can be used by marketing scripts. You only need to declare the object variables you use.

## Learn more at these links (in Russian)
- [Digital Data Layer Website](https://www.data-layer.net)
- [Driveback Blog](http://blog.driveback.ru)


## Supported Magento Versions

We've tested the followed versions. Please submit Github Issues with detailed description if you find any bugs.

1.6.x, 1.7.x, 1.8.x , 1.9.x CE
1.6.x, 1.7.x, 1.8.x Enterprise

## Installation

To install the extension:
 * Drop the `app` folder in this repository into your root Magento directory (making sure you perform a merge, not a replace)
 * Log out and back into the Magento Admin interface.
 * Navigate to the `System -> Configuration` panel from the top navigation bar.
 * Configuration options can be found under `Digital Data Layer`.
 * Test site functionality thoroughly after installation.
 
## Configuration

 * Enable Digital Data Layer turns on the object for the front end.
 * Enable Digital Data Manager adds [Digital Data Manager](https://github.com/driveback/digital-data-manager) script to the head of the page.
 
### Magento Connect

This is the recommanded way of installing the extenion. Get your extension key on [the extension page](https://www.magentocommerce.com/magento-connect/catalog/product/view/id/30682/) and install the extension in your Magento Connect extension manager.

## License

The Digital Data Manager is licensed under the MIT license. See [License File](LICENSE.txt) for more information.

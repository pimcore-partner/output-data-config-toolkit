/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


pimcore.registerNS("pimcore.bundle.outputDataConfigToolkit.outputDataConfigElements.value.DimensionUnit");

pimcore.bundle.outputDataConfigToolkit.outputDataConfigElements.value.DimensionUnitFieldText = Class.create(pimcore.bundle.outputDataConfigToolkit.outputDataConfigElements.value.DimensionUnitField, {
    type: "value",
    class: "DimensionUnitFieldText"
});
<?php
if (class_exists('WpOrg\Requests\Request')) {
    class_alias('WpOrg\Requests\Request', 'DubkiiBooking\ThirdParty\Requests\Request');
    class_alias('WpOrg\Requests\Auth\Basic', 'DubkiiBooking\ThirdParty\Requests\Auth\Basic');
    class_alias('WpOrg\Requests\Capability', 'DubkiiBooking\ThirdParty\Requests\Capability');
    class_alias('WpOrg\Requests\Cookie\Jar', 'DubkiiBooking\ThirdParty\Requests\Cookie\Jar');
    class_alias('WpOrg\Requests\Exception', 'DubkiiBooking\ThirdParty\Requests\Exception');
    class_alias('WpOrg\Requests\Exception\InvalidArgument', 'DubkiiBooking\ThirdParty\Requests\Exception\InvalidArgument');
    class_alias('WpOrg\Requests\Hooks', 'DubkiiBooking\ThirdParty\Requests\Hooks');
    class_alias('WpOrg\Requests\IdnaEncoder', 'DubkiiBooking\ThirdParty\Requests\IdnaEncoder');
    class_alias('WpOrg\Requests\Iri', 'DubkiiBooking\ThirdParty\Requests\Iri');
    class_alias('WpOrg\Requests\Proxy\Http', 'DubkiiBooking\ThirdParty\Requests\Proxy\Http');
    class_alias('WpOrg\Requests\Response', 'DubkiiBooking\ThirdParty\Requests\Response');
    class_alias('WpOrg\Requests\Transport\Curl', 'DubkiiBooking\ThirdParty\Requests\Transport\Curl');
    class_alias('WpOrg\Requests\Transport\Fsockopen', 'DubkiiBooking\ThirdParty\Requests\Transport\Fsockopen');
    class_alias('WpOrg\Requests\Utility\InputValidator', 'DubkiiBooking\ThirdParty\Requests\Utility\InputValidator');
}

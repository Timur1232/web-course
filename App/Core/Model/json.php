<?php namespace App\Core\Model;
use App\Core\Helpers\Log;
use Attribute;
use ReflectionClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Json {
    public function __construct(
        public ?string $json_name = null,
    ) {}

    public static function jsonify(mixed $obj, bool $no_attributes = false): array {
        $r = new ReflectionClass($obj);
        $fields = $r->getProperties();
        $result = [];
        $count = 0;
        foreach ($fields as $field) {
            $attrs;
            if (!$no_attributes) $attrs = $field->getAttributes(Json::class);
            if ($no_attributes || count($attrs) !== 0) {
                if ($field->isPrivate()) {
                    Log::warning(__METHOD__.": Unable to serialize private property: {$field->getName()}");
                    continue;
                }
                $field_name = $field->getName();
                $attr = $attrs[0]->newInstance();
                // TODO: Maybe not make it recursive?
                $json_field = self::jsonify($obj->$field_name, $no_attributes);
                $result[$attr->json_name ?? $field_name] = $json_field;
                $count++;
            }
        }
        if (!$no_attributes && $count === 0) {
            Log::warning(__METHOD__.': Serializable object have no Json attributes on properties. Set $no_attributes parameter to true to serialize any object.');
        }
        return $result;
    }
}

import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import promotionService from '../services/promotionService';
import Card from '../components/Card';
import Loading from '../components/Loading';
import { COLORS } from '../constants/colors';

const PromotionsScreen = () => {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [promotions, setPromotions] = useState([]);

  useEffect(() => {
    loadPromotions();
  }, []);

  const loadPromotions = async () => {
    try {
      const data = await promotionService.getPromotions();
      setPromotions(data.promotions || data);
    } catch (error) {
      console.error('Error loading promotions:', error);
      Alert.alert('Erreur', 'Impossible de charger les promotions');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const onRefresh = () => {
    setRefreshing(true);
    loadPromotions();
  };

  const renderPromotion = ({ item }) => (
    <Card>
      <View style={styles.promoHeader}>
        <Text style={styles.promoCode}>{item.code}</Text>
        {item.online_only && (
          <View style={styles.badge}>
            <Text style={styles.badgeText}>En ligne</Text>
          </View>
        )}
      </View>

      <Text style={styles.promoName}>{item.name}</Text>
      <Text style={styles.promoDescription}>{item.description}</Text>

      <View style={styles.promoDetails}>
        <View style={styles.detail}>
          <Text style={styles.detailLabel}>Réduction</Text>
          <Text style={styles.detailValue}>
            {item.discount_type === 'percentage'
              ? `${item.discount_value}%`
              : `${item.discount_value}€`}
          </Text>
        </View>

        <View style={styles.detail}>
          <Text style={styles.detailLabel}>Valide jusqu'au</Text>
          <Text style={styles.detailValue}>
            {new Date(item.valid_until).toLocaleDateString('fr-FR')}
          </Text>
        </View>
      </View>

      {item.new_customers_only && (
        <Text style={styles.condition}>⚠️ Nouveaux clients uniquement</Text>
      )}
    </Card>
  );

  if (loading) {
    return <Loading />;
  }

  return (
    <SafeAreaView style={styles.container}>
      <FlatList
        data={promotions}
        renderItem={renderPromotion}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        ListEmptyComponent={
          <Card>
            <Text style={styles.emptyText}>Aucune promotion disponible</Text>
          </Card>
        }
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  listContent: {
    padding: 16,
  },
  promoHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  promoCode: {
    fontSize: 18,
    fontWeight: 'bold',
    color: COLORS.primary,
  },
  badge: {
    backgroundColor: COLORS.primary + '20',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 4,
  },
  badgeText: {
    fontSize: 12,
    color: COLORS.primary,
    fontWeight: '600',
  },
  promoName: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  promoDescription: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 12,
    lineHeight: 20,
  },
  promoDetails: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    borderTopWidth: 1,
    borderTopColor: COLORS.divider,
    paddingTop: 12,
  },
  detail: {
    flex: 1,
  },
  detailLabel: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginBottom: 4,
  },
  detailValue: {
    fontSize: 14,
    fontWeight: '600',
    color: COLORS.text.primary,
  },
  condition: {
    fontSize: 12,
    color: COLORS.warning,
    marginTop: 8,
    fontStyle: 'italic',
  },
  emptyText: {
    textAlign: 'center',
    color: COLORS.text.secondary,
    fontSize: 14,
  },
});

export default PromotionsScreen;
